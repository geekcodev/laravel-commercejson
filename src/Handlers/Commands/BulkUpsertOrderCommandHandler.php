<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\BulkUpsertOrderCommand;
use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Data\OrderBulkUpdateItemData;
use GeekCo\CommerceJson\Data\OrderDeliveryTrackData;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use GeekCo\CommerceJson\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BulkUpsertOrderCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
    ) {}

    public static function buildItems(OrderBulkUpdateItemData $bulkItem): ?array
    {
        if (! $bulkItem->items) {
            return null;
        }

        $defaultCurrency = CurrencyEnum::tryFrom(config('commercejson.default_currency')) ?? CurrencyEnum::RUB;
        $items = [];

        foreach ($bulkItem->items as $item) {
            if ($item->product_id === null) {
                Log::warning('Skipping bulk order item without product_id', [
                    'item_id' => $item->id,
                ]);

                continue;
            }

            $currency = $item->price ? $item->price->currency->value : $defaultCurrency->value;
            $amount = $item->price ? $item->price->amount : '0';
            $items[] = [
                'id' => $item->id ?? (string) Str::uuid(),
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity ?? 1,
                'price' => ['amount' => $amount, 'currency' => $currency],
                'total' => ['amount' => $amount, 'currency' => $currency],
            ];
        }

        return $items;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof BulkUpsertOrderCommand);

        return DB::transaction(function () use ($command) {
            $existing = $this->orderRepository->find($command->id);

            if (! $existing instanceof Order && $command->external_id !== null) {
                $existing = $this->orderRepository->findByExternalId($command->external_id);
            }

            if ($existing instanceof Order) {
                return $this->updateExisting($existing, $command);
            }

            return $this->createNew($command);
        });
    }

    private function createNew(BulkUpsertOrderCommand $command): Order
    {
        $order = $this->orderRepository->create([
            'id' => $command->id ?? (string) Str::uuid(),
            'number' => 'ORD-'.date('Ymd').'-'.Str::upper(Str::random(6)),
            'external_id' => $command->external_id,
            'status' => $command->status ?? OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order,
            'comment' => $command->comment,
        ]);

        assert($order instanceof Order);

        if ($command->items !== null) {
            $this->syncItems($order, $command->items);
        }

        if ($command->deliveryTrack) {
            $this->applyDeliveryTracking($order, $command->deliveryTrack);
        }

        if ($command->linkedDocuments !== null) {
            $this->orderRepository->syncLinkedDocuments($order, $command->linkedDocuments);
        }

        return $order->fresh('linkedDocuments');
    }

    private function updateExisting(Order $order, BulkUpsertOrderCommand $command): Order
    {
        $updates = array_filter([
            'external_id' => $command->external_id,
            'status' => $command->status,
            'comment' => $command->comment,
        ], fn ($v) => $v !== null);

        if (! empty($updates)) {
            $order->update($updates);
        }

        if ($command->items !== null) {
            $order->items()->delete();
            $this->syncItems($order, $command->items);
        }

        if ($command->deliveryTrack) {
            $this->applyDeliveryTracking($order, $command->deliveryTrack);
        }

        if ($command->linkedDocuments !== null) {
            $this->orderRepository->syncLinkedDocuments($order, $command->linkedDocuments);
        }

        return $order->fresh('linkedDocuments');
    }

    private function syncItems(Order $order, array $items): void
    {
        $defaultCurrency = CurrencyEnum::tryFrom(config('commercejson.default_currency')) ?? CurrencyEnum::RUB;
        $currency = null;
        $totalSum = 0;

        $productIds = array_unique(array_filter(array_map(fn ($i) => $i['product_id'] ?? null, $items)));
        $products = ! empty($productIds)
            ? $this->productRepository->findMany($productIds)->keyBy('id')
            : collect();

        foreach ($items as $item) {
            $itemCurrency = $item['price']['currency'] ?? $defaultCurrency->value;

            if ($currency === null) {
                $currency = $itemCurrency;
            } elseif ($currency !== $itemCurrency) {
                Log::warning('Mixed currencies in bulk order items — using first currency', [
                    'order_id' => $order->id,
                    'first_currency' => $currency,
                    'mixed_currency' => $itemCurrency,
                ]);
            }

            $priceAmount = $item['price']['amount'] ?? '0';
            $quantity = (float) ($item['quantity'] ?? 1);
            $lineTotal = $item['total']['amount'] ?? number_format($priceAmount * $quantity, 2, '.', '');
            $lineCurrency = $item['total']['currency'] ?? $itemCurrency;
            $totalSum += (float) $lineTotal;

            $productId = $item['product_id'] ?? '';
            $product = $products->get($productId);

            $order->items()->create([
                'id' => $item['id'] ?? (string) Str::uuid(),
                'product_id' => $productId,
                'variant_id' => $item['variant_id'] ?? null,
                'warehouse_id' => $item['warehouse_id'] ?? null,
                'product_name' => $product ? $product->name : $productId,
                'product_code' => $product?->code,
                'unit_code' => $product?->unit_code,
                'unit_short_name' => $product?->unit_short_name,
                'unit_full_name' => $product?->unit_full_name,
                'unit_international' => $product?->unit_international,
                'quantity' => $item['quantity'] ?? 1,
                'price_amount' => $priceAmount,
                'price_currency' => $itemCurrency,
                'total_amount' => $lineTotal,
                'total_currency' => $lineCurrency,
            ]);
        }

        $order->update([
            'totals_subtotal_amount' => number_format($totalSum, 2, '.', ''),
            'totals_subtotal_currency' => $currency ?? $defaultCurrency->value,
            'totals_total_amount' => number_format($totalSum, 2, '.', ''),
            'totals_total_currency' => $currency ?? $defaultCurrency->value,
        ]);
    }

    private function applyDeliveryTracking(Order $order, OrderDeliveryTrackData $deliveryTrack): void
    {
        $updates = array_filter([
            'delivery_tracking_number' => $deliveryTrack->tracking_number,
            'delivery_shipped_at' => $deliveryTrack->shipped_at,
            'delivery_estimated_date' => $deliveryTrack->estimated_date,
        ], fn ($v) => $v !== null);

        if (! empty($updates)) {
            $order->update($updates);
        }
    }
}
