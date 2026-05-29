<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\BulkUpsertOrderCommand;
use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Data\OrderDeliveryTrackData;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BulkUpsertOrderCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrderRepository $repository,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof BulkUpsertOrderCommand);

        return DB::transaction(function () use ($command) {
            $existing = $this->repository->find($command->id);

            if ($existing instanceof Order) {
                return $this->updateExisting($existing, $command);
            }

            return $this->createNew($command);
        });
    }

    private function createNew(BulkUpsertOrderCommand $command): Order
    {
        $order = $this->repository->create([
            'id' => $command->id,
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

        return $order->fresh();
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

        return $order->fresh();
    }

    private function syncItems(Order $order, array $items): void
    {
        $currency = CurrencyEnum::RUB->value;
        $totalSum = 0;

        foreach ($items as $item) {
            $itemCurrency = $item['price']['currency'] ?? CurrencyEnum::RUB->value;
            $priceAmount = $item['price']['amount'] ?? '0';
            $quantity = (float) ($item['quantity'] ?? 1);
            $lineTotal = $item['total']['amount'] ?? number_format($priceAmount * $quantity, 2, '.', '');
            $lineCurrency = $item['total']['currency'] ?? $itemCurrency;
            $totalSum += (float) $lineTotal;

            $order->items()->create([
                'id' => $item['id'] ?? (string) Str::uuid(),
                'product_id' => $item['product_id'] ?? '',
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'price_amount' => $priceAmount,
                'price_currency' => $itemCurrency,
                'total_amount' => $lineTotal,
                'total_currency' => $lineCurrency,
            ]);

            $currency = $itemCurrency;
        }

        $order->update([
            'totals_subtotal_amount' => number_format($totalSum, 2, '.', ''),
            'totals_subtotal_currency' => $currency,
            'totals_total_amount' => number_format($totalSum, 2, '.', ''),
            'totals_total_currency' => $currency,
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
