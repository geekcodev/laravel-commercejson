<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Console\Commands;

use GeekCo\CommerceJson\Console\Concerns\InteractsWithExchange;
use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Data\OrderImportData;
use GeekCo\CommerceJson\Events\OrderExported;
use GeekCo\CommerceJson\Services\OrderService;
use Illuminate\Console\Command;

/**
 * Команда: Экспорт заказов в ERP
 */
class ExportOrdersCommand extends Command
{
    use InteractsWithExchange;

    protected $signature = 'commercejson:export-orders
                            {--limit=50 : Количество заказов для экспорта}
                            {--status= : Фильтр по статусу}
                            {--since= : Экспорт заказов обновлённых после даты}
                            {--queue : Использовать очередь}';

    protected $description = 'Экспортировать заказы в ERP систему через CommerceJSON API';

    public function handle(OrderService $orderService): int
    {
        $this->info('Starting orders export to ERP...');

        return $this->withErrorHandling(function () use ($orderService) {
            // Проверка соединения
            if (! $this->checkConnection()) {
                return 1;
            }

            $limit = min((int) $this->option('limit'), 100);
            $status = $this->option('status') ?? 'new';
            $since = $this->option('since')
                ? new \DateTime($this->option('since'))
                : null;

            $this->newLine();
            $this->info("Fetching orders for export (limit: {$limit})...");

            if ($since) {
                $this->line('Since: '.$since->format('Y-m-d H:i:s'));
            }

            // Получение заказов для экспорта
            if ($since) {
                $orderList = $orderService->getOrdersForIncrementalExport($since, $limit);
            } else {
                $orderList = $orderService->getNewOrdersForExport($limit);
            }

            $ordersCount = count($orderList->orders);

            if ($ordersCount === 0) {
                $this->warn('No orders found for export.');

                return 0;
            }

            $this->line("Found: {$ordersCount} orders for export");

            // Подготовка данных для экспорта
            $this->newLine();
            $this->info('Preparing orders for export...');

            $exportData = [];
            $stats = ['exported' => 0, 'failed' => 0];

            /** @var OrderData $order */
            foreach ($orderList->orders as $order) {
                try {
                    // Формирование данных для экспорта
                    $exportOrder = [
                        'id' => $order->id,
                        'external_id' => $order->external_id,
                        'status' => $order->status,
                        'document_type' => $order->document_type,
                        'comment' => $order->comment,
                        'delivery' => [
                            'tracking_number' => $order->delivery->tracking_number,
                            'shipped_at' => $order->delivery->shipped_at?->toIso8601String(),
                            'estimated_date' => $order->delivery->estimated_date,
                        ],
                        'custom_attributes' => $order->custom_attributes
                            ->get()
                            ->map(fn ($attr) => [
                                'key' => $attr->key,
                                'value' => $attr->value_string ?? $attr->value_number ?? $attr->value_boolean,
                            ])
                            ->toArray(),
                    ];

                    $exportData[] = $exportOrder;
                    $stats['exported']++;
                } catch (\Exception $e) {
                    $stats['failed']++;
                    $this->error("Failed to prepare order {$order->id}: ".$e->getMessage());
                }
            }

            // Отправка в API
            if (! empty($exportData) && ! $this->option('dry-run')) {
                $this->newLine();
                $this->info('Sending orders to CommerceJSON API...');

                $importResult = $orderService->importOrders(
                    OrderImportData::from(['orders' => $exportData])
                );

                if ($importResult->success) {
                    $this->line("Successfully exported: {$importResult->processed} orders");
                }

                if (! empty($importResult->errors)) {
                    $this->newLine();
                    $this->error('Export errors:');
                    foreach ($importResult->errors as $error) {
                        $this->line("  - {$error->message}");
                    }
                }
            } else {
                $this->warn('Dry run mode - no data sent to API.');
            }

            // Итоговая таблица
            $this->newLine();
            $this->table(
                ['Action', 'Count'],
                [
                    ['Exported', $stats['exported']],
                    ['Failed', $stats['failed']],
                    ['Total', $ordersCount],
                ]
            );

            // Dispatch event
            if ($stats['exported'] > 0) {
                event(new OrderExported($stats['exported']));
            }

            $this->newLine();
            $this->info('<fg=green>✓ Orders export completed successfully!</>');

            return 0;
        });
    }
}
