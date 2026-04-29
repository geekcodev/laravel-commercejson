<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Jobs\Export;

use GeekCo\CommerceJson\Data\OrderImportData;
use GeekCo\CommerceJson\Events\OrderExported;
use GeekCo\CommerceJson\Jobs\Concerns\InteractsWithCommerceJson;
use GeekCo\CommerceJson\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job: Экспорт заказов в ERP
 */
class ExportOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithCommerceJson;

    public int $timeout = 120;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $limit = 50,
        protected ?string $status = null,
        protected ?string $since = null
    ) {
        $this->onQueue(config('commercejson.exchange.queue.export_queue', 'commercejson-export'));
        $this->onConnection(config('commercejson.exchange.queue.connection', 'sync'));
    }

    /**
     * Execute the job.
     */
    public function handle(OrderService $orderService): void
    {
        $this->logJobAction("Starting orders export (limit: {$this->limit})");

        if (! $this->checkConnection()) {
            $this->fail(new \RuntimeException('Connection to CommerceJSON API failed'));

            return;
        }

        // Получение заказов для экспорта
        if ($this->since) {
            $orderList = $orderService->getOrdersForIncrementalExport(
                new \DateTime($this->since),
                $this->limit
            );
        } else {
            $orderList = $orderService->getNewOrdersForExport($this->limit);
        }

        if (count($orderList->orders) === 0) {
            $this->logJobAction('No orders found for export');

            return;
        }

        $this->logJobAction('Found '.count($orderList->orders).' orders for export');

        // Подготовка данных для экспорта
        $exportData = [];
        foreach ($orderList->orders as $order) {
            $exportData[] = [
                'id' => $order->id,
                'external_id' => $order->external_id,
                'status' => $order->status,
                'document_type' => $order->document_type,
                'comment' => $order->comment,
            ];
        }

        // Отправка в API
        $importResult = $orderService->importOrders(
            OrderImportData::from(['orders' => $exportData])
        );

        $this->logJobAction(
            'Orders export completed',
            [
                'exported' => $importResult->processed,
                'success' => $importResult->success,
                'errors' => count($importResult->errors),
            ]
        );

        if (! $importResult->success) {
            foreach ($importResult->errors as $error) {
                $this->logJobError("Export error: {$error->message}");
            }
        }

        // Dispatch event
        event(new OrderExported($importResult->processed));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->logJobError('Orders export job failed: '.$exception->getMessage());
    }
}
