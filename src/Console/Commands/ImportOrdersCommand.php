<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Console\Commands;

use GeekCo\CommerceJson\Console\Concerns\InteractsWithExchange;
use GeekCo\CommerceJson\Events\OrderImported;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\OrderItem;
use GeekCo\CommerceJson\Models\OrderItemTax;
use GeekCo\CommerceJson\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Команда: Импорт заказов
 */
class ImportOrdersCommand extends Command
{
    use InteractsWithExchange;

    protected $signature = 'commercejson:import-orders
                            {--page=1 : Номер страницы}
                            {--limit=50 : Количество на странице}
                            {--status= : Фильтр по статусу}
                            {--document-type= : Фильтр по типу документа}
                            {--updated-after= : Дата для инкрементального импорта}
                            {--include-deleted : Включая удалённые}
                            {--queue : Использовать очередь}
                            {--no-sync : Не синхронизировать с БД}';

    protected $description = 'Импортировать заказы из CommerceJSON API';

    public function handle(OrderService $orderService): int
    {
        $this->info('Starting orders import...');

        return $this->withErrorHandling(function () use ($orderService) {
            // Проверка соединения
            if (! $this->checkConnection()) {
                return 1;
            }

            $page = (int) $this->option('page');
            $limit = min((int) $this->option('limit'), 100);
            $status = $this->option('status');
            $documentType = $this->option('document-type');
            $updatedAfter = $this->option('updated-after')
                ? new \DateTime($this->option('updated-after'))
                : null;
            $includeDeleted = $this->option('include-deleted');

            $this->newLine();
            $this->info("Fetching orders (page: {$page}, limit: {$limit})...");

            if ($status) {
                $this->line("Status filter: {$status}");
            }

            if ($documentType) {
                $this->line("Document type filter: {$documentType}");
            }

            if ($updatedAfter) {
                $this->line('Updated after: '.$updatedAfter->format('Y-m-d H:i:s'));
            }

            if ($includeDeleted) {
                $this->line('Including deleted orders');
            }

            // Получение заказов
            $orderList = $orderService->getOrders(
                page: $page,
                limit: $limit,
                status: $status,
                documentType: $documentType,
                updatedAfter: $updatedAfter,
                includeDeleted: $includeDeleted
            );

            $ordersCount = count($orderList->orders);
            $this->line("Received: {$ordersCount} orders");

            if ($this->option('no-sync')) {
                $this->warn('Skipping database sync (--no-sync flag)');

                return 0;
            }

            // Синхронизация с БД
            $this->newLine();
            $this->info('Synchronizing orders with database...');

            $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];

            $this->withProgressBar($orderList->orders, function ($orderData) use ($stats) {
                try {
                    DB::transaction(function () use ($orderData, $stats) {
                        // Синхронизация заказа
                        $order = Order::updateOrCreate(
                            ['id' => $orderData->id],
                            [
                                'number' => $orderData->number,
                                'external_id' => $orderData->externalId,
                                'status' => $orderData->status->value,
                                'document_type' => $orderData->documentType->value ?? 'order',
                                'role' => $orderData->role?->value,
                                'base_currency' => $orderData->baseCurrency,
                                'exchange_rate' => $orderData->exchangeRate,
                                'payment_terms' => $orderData->paymentTerms,
                                'counterparty_id' => $orderData->counterpartyId,
                                'warehouse_id' => $orderData->warehouseId,
                                'comment' => $orderData->comment,
                                'customer_name' => $orderData->customer?->name,
                                'customer_phone' => $orderData->customer?->phone,
                                'customer_email' => $orderData->customer?->email,
                                'customer_counterparty_id' => $orderData->customer?->counterpartyId,
                                'delivery_type' => $orderData->delivery?->type,
                                'delivery_address_full' => $orderData->delivery?->address?->full,
                                'delivery_cost_amount' => $orderData->delivery?->cost?->amount,
                                'delivery_cost_currency' => $orderData->delivery?->cost?->currency,
                                'delivery_tracking_number' => $orderData->delivery?->trackingNumber,
                                'delivery_shipped_at' => $orderData->delivery?->shippedAt,
                                'delivery_estimated_date' => $orderData->delivery?->estimatedDate,
                                'payment_type' => $orderData->payment?->type,
                                'payment_status' => $orderData->payment?->status,
                                'payment_amount' => $orderData->payment?->amount?->amount,
                                'payment_currency' => $orderData->payment?->amount?->currency,
                                'payment_paid_at' => $orderData->payment?->paidAt,
                                'totals_subtotal_amount' => $orderData->totals->subtotal->amount,
                                'totals_subtotal_currency' => $orderData->totals->subtotal->currency,
                                'totals_discount_amount' => $orderData->totals->discount?->amount,
                                'totals_discount_currency' => $orderData->totals->discount?->currency,
                                'totals_delivery_amount' => $orderData->totals->delivery?->amount,
                                'totals_delivery_currency' => $orderData->totals->delivery?->currency,
                                'totals_tax_amount' => $orderData->totals->tax?->amount,
                                'totals_tax_currency' => $orderData->totals->tax?->currency,
                                'totals_total_amount' => $orderData->totals->total->amount,
                                'totals_total_currency' => $orderData->totals->total->currency,
                                'deleted_at' => $orderData->deletedAt,
                            ]
                        );

                        if ($order->wasRecentlyCreated) {
                            $stats['created']++;
                        } else {
                            $stats['updated']++;
                        }

                        // Синхронизация позиций заказа
                        if (! empty($orderData->items)) {
                            foreach ($orderData->items as $itemData) {
                                $orderItem = OrderItem::updateOrCreate(
                                    ['id' => $itemData->id],
                                    [
                                        'order_id' => $order->id,
                                        'product_id' => $itemData->productId,
                                        'variant_id' => $itemData->variantId,
                                        'product_name' => $itemData->productName,
                                        'product_code' => $itemData->productCode,
                                        'quantity' => $itemData->quantity,
                                        'unit_code' => $itemData->unit?->code,
                                        'unit_short_name' => $itemData->unit?->shortName,
                                        'price_amount' => $itemData->price->amount,
                                        'price_currency' => $itemData->price->currency,
                                        'discount_amount' => $itemData->discount?->amount,
                                        'discount_currency' => $itemData->discount?->currency,
                                        'total_amount' => $itemData->total->amount,
                                        'total_currency' => $itemData->total->currency,
                                        'country_of_origin' => $itemData->countryOfOrigin,
                                        'customs_declaration_number' => $itemData->customsDeclarationNumber,
                                        'tax_rate' => $itemData->taxRate,
                                    ]
                                );

                                // Синхронизация налогов позиции
                                if (! empty($itemData->taxes)) {
                                    foreach ($itemData->taxes as $taxData) {
                                        OrderItemTax::updateOrCreate(
                                            [
                                                'order_item_id' => $orderItem->id,
                                                'type' => $taxData->type,
                                            ],
                                            [
                                                'rate' => $taxData->rate,
                                                'amount' => $taxData->amount->amount,
                                                'currency' => $taxData->amount->currency,
                                                'is_included' => $taxData->isIncluded,
                                            ]
                                        );
                                    }
                                }
                            }
                        }
                    });
                } catch (\Exception $e) {
                    $stats['failed']++;
                    if ($this->option('verbose')) {
                        $this->error('Failed to sync order: '.$e->getMessage());
                    }
                }
            });

            // Итоговая таблица
            $this->newLine();
            $this->table(
                ['Action', 'Count'],
                [
                    ['Created', $stats['created']],
                    ['Updated', $stats['updated']],
                    ['Failed', $stats['failed']],
                    ['Total', $ordersCount],
                ]
            );

            // Dispatch events
            foreach ($orderList->orders as $orderData) {
                event(new OrderImported(
                    $orderData->id,
                    $orderData->status->value
                ));
            }

            $this->newLine();
            $this->info('<fg=green>✓ Orders import completed successfully!</>');

            return 0;
        });
    }
}
