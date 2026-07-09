<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Console\Commands;

use GeekCo\CommerceJson\Console\Concerns\InteractsWithExchange;
use GeekCo\CommerceJson\Data\OrderItemData;
use GeekCo\CommerceJson\Data\OrderItemTaxData;
use GeekCo\CommerceJson\Events\OrderImported;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use GeekCo\CommerceJson\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Команда: Импорт заказов
 */
class ImportOrdersCommand extends Command
{
    use InteractsWithExchange;

    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {
        parent::__construct();
    }

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
                        /** @var Order $order */
                        $order = $this->orderRepository->updateOrCreate(
                            ['id' => $orderData->id],
                            [
                                'number' => $orderData->number,
                                'external_id' => $orderData->external_id,
                                'status' => $orderData->status->value,
                                'document_type' => $orderData->document_type->value ?? 'order',
                                'role' => $orderData->role?->value,
                                'base_currency' => $orderData->base_currency,
                                'exchange_rate' => $orderData->exchange_rate,
                                'payment_terms' => $orderData->payment_terms,
                                'counterparty_id' => $orderData->counterparty_id,
                                'warehouse_id' => $orderData->warehouse_id,
                                'comment' => $orderData->comment,
                                'customer_name' => $orderData->customer?->name,
                                'customer_phone' => $orderData->customer?->phone,
                                'customer_email' => $orderData->customer?->email,
                                'customer_counterparty_id' => $orderData->customer?->counterparty_id,
                                'delivery_type' => $orderData->delivery?->type,
                                'delivery_address_full' => $orderData->delivery?->address?->full,
                                'delivery_cost_amount' => $orderData->delivery?->cost?->amount,
                                'delivery_cost_currency' => $orderData->delivery?->cost?->currency,
                                'delivery_tracking_number' => $orderData->delivery?->tracking_number,
                                'delivery_shipped_at' => $orderData->delivery?->shipped_at,
                                'delivery_estimated_date' => $orderData->delivery?->estimated_date,
                                'payment_type' => $orderData->payment?->type,
                                'payment_status' => $orderData->payment?->status,
                                'payment_amount' => $orderData->payment?->amount?->amount,
                                'payment_currency' => $orderData->payment?->amount?->currency,
                                'payment_paid_at' => $orderData->payment?->paid_at,
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
                                'deleted_at' => $orderData->deleted_at,
                            ]
                        );

                        if ($order->wasRecentlyCreated) {
                            $stats['created']++;
                        } else {
                            $stats['updated']++;
                        }

                        // Синхронизация позиций заказа
                        if (! empty($orderData->items)) {
                            /** @var OrderItemData $itemData */
                            foreach ($orderData->items as $itemData) {
                                $orderItem = $this->orderRepository->updateOrCreateItem(
                                    $order,
                                    $itemData->toArray()
                                );

                                // Синхронизация налогов позиции
                                if (! empty($itemData->taxes)) {
                                    /** @var OrderItemTaxData $taxData */
                                    foreach ($itemData->taxes as $taxData) {
                                        $this->orderRepository->updateOrCreateItemTax(
                                            $orderItem,
                                            $taxData->toArray()
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
