<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\PatchOrderCommand;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use GeekCo\CommerceJson\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PatchOrderCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof PatchOrderCommand);

        return DB::transaction(function () use ($command) {
            $order = $this->orderRepository->findOrFail($command->id);
            assert($order instanceof Order);

            $patch = $command->patchData;

            $updates = array_filter([
                'status' => $patch->status,
                'external_id' => $patch->external_id,
                'comment' => $patch->comment,
            ], fn ($v) => $v !== null);

            if ($patch->payment !== null) {
                $p = $patch->payment;
                $updates = array_merge($updates, array_filter([
                    'payment_status' => $p->status,
                    'payment_amount' => $p->amount?->amount,
                    'payment_currency' => $p->amount?->currency,
                    'payment_paid_at' => $p->paid_at,
                    'payment_transaction_id' => $p->transaction_id,
                ], fn ($v) => $v !== null));
            }

            if ($patch->delivery !== null) {
                $d = $patch->delivery;
                $updates = array_merge($updates, array_filter([
                    'delivery_tracking_number' => $d->tracking_number,
                    'delivery_shipped_at' => $d->shipped_at,
                    'delivery_estimated_date' => $d->estimated_date,
                ], fn ($v) => $v !== null));
            }

            if (! empty($updates)) {
                $order->update($updates);
            }

            if ($patch->items !== null) {
                $order->items()->delete();
                $this->syncItems($order, $patch->items);
            }

            if ($patch->linked_documents !== null) {
                $this->orderRepository->syncLinkedDocuments($order, $patch->linked_documents);
            }

            return $order->fresh(['items', 'linkedDocuments']);
        });
    }

    private function syncItems(Order $order, array $items): void
    {
        $defaultCurrency = CurrencyEnum::tryFrom(config('commercejson.default_currency')) ?? CurrencyEnum::RUB;
        $currency = null;
        $totalSum = 0;

        $productIds = array_unique(array_filter(array_map(
            fn ($i) => $i->product_id ?? null,
            $items
        )));
        $products = ! empty($productIds)
            ? $this->productRepository->findMany($productIds)->keyBy('id')
            : collect();

        foreach ($items as $item) {
            $itemCurrency = $item->price !== null ? $item->price->currency->value : $defaultCurrency->value;

            if ($currency === null) {
                $currency = $itemCurrency;
            } elseif ($currency !== $itemCurrency) {
                Log::warning('Mixed currencies in order items — using first currency', [
                    'order_id' => $order->id,
                    'first_currency' => $currency,
                    'mixed_currency' => $itemCurrency,
                ]);
            }

            $priceAmount = $item->price !== null ? $item->price->amount : '0';
            $quantity = (float) ($item->quantity ?? 1);
            $lineTotal = number_format((float) $priceAmount * $quantity, 2, '.', '');
            $totalSum += (float) $lineTotal;

            $productId = $item->product_id ?? '';
            $product = $products->get($productId);

            $order->items()->create([
                'id' => $item->id ?? (string) Str::uuid(),
                'product_id' => $productId,
                'variant_id' => $item->variant_id ?? null,
                'product_name' => $product ? $product->name : $productId,
                'product_code' => $product?->code,
                'unit_code' => $product?->unit_code,
                'unit_short_name' => $product?->unit_short_name,
                'unit_full_name' => $product?->unit_full_name,
                'unit_international' => $product?->unit_international,
                'quantity' => $item->quantity ?? 1,
                'price_amount' => $priceAmount,
                'price_currency' => $itemCurrency,
                'total_amount' => $lineTotal,
                'total_currency' => $itemCurrency,
            ]);
        }

        $order->update([
            'totals_subtotal_amount' => number_format($totalSum, 2, '.', ''),
            'totals_subtotal_currency' => $currency ?? $defaultCurrency->value,
            'totals_total_amount' => number_format($totalSum, 2, '.', ''),
            'totals_total_currency' => $currency ?? $defaultCurrency->value,
        ]);
    }
}
