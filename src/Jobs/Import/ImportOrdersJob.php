<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Jobs\Import;

use GeekCo\CommerceJson\Events\OrderImported;
use GeekCo\CommerceJson\Jobs\Concerns\InteractsWithCommerceJson;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\OrderItem;
use GeekCo\CommerceJson\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Job: Импорт страницы заказов
 */
class ImportOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithCommerceJson;

    public int $timeout = 300;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $page = 1,
        protected int $limit = 50,
        protected ?string $status = null,
        protected ?string $documentType = null,
        protected ?string $updatedAfter = null,
        protected bool $includeDeleted = false
    ) {
        $this->onQueue(config('commercejson.exchange.queue.import_queue', 'commercejson-import'));
        $this->onConnection(config('commercejson.exchange.queue.connection', 'sync'));
    }

    /**
     * Execute the job.
     */
    public function handle(OrderService $orderService): void
    {
        $this->logJobAction("Starting orders import (page: {$this->page}, limit: {$this->limit})");

        if (! $this->checkConnection()) {
            $this->fail(new \RuntimeException('Connection to CommerceJSON API failed'));

            return;
        }

        // Получение заказов
        $orderList = $orderService->getOrders(
            page: $this->page,
            limit: $this->limit,
            status: $this->status,
            documentType: $this->documentType,
            updatedAfter: $this->updatedAfter ? new \DateTime($this->updatedAfter) : null,
            includeDeleted: $this->includeDeleted
        );

        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];

        // Синхронизация с БД
        foreach ($orderList->orders as $orderData) {
            try {
                DB::transaction(function () use ($orderData, $stats) {
                    // Синхронизация заказа
                    $order = Order::updateOrCreate(
                        ['id' => $orderData->id],
                        [
                            'number' => $orderData->number,
                            'external_id' => $orderData->externalId,
                            'status' => $orderData->status->value,
                            'document_type' => $orderData->documentType?->value ?? 'order',
                            'counterparty_id' => $orderData->counterpartyId,
                            'warehouse_id' => $orderData->warehouseId,
                            'customer_name' => $orderData->customer?->name,
                            'customer_email' => $orderData->customer?->email,
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

                    // Синхронизация позиций (упрощённо)
                    if (! empty($orderData->items)) {
                        foreach ($orderData->items as $itemData) {
                            OrderItem::updateOrCreate(
                                ['id' => $itemData->id],
                                [
                                    'order_id' => $order->id,
                                    'product_id' => $itemData->productId,
                                    'quantity' => $itemData->quantity,
                                    'price_amount' => $itemData->price->amount,
                                    'total_amount' => $itemData->total->amount,
                                ]
                            );
                        }
                    }
                });

                // Dispatch event для каждого заказа
                event(new OrderImported(
                    $orderData->id,
                    $orderData->status->value
                ));
            } catch (\Exception $e) {
                $stats['failed']++;
                $this->logJobError("Failed to sync order {$orderData->id}: ".$e->getMessage());
            }
        }

        $this->logJobAction(
            'Orders import completed',
            [
                'page' => $this->page,
                'created' => $stats['created'],
                'updated' => $stats['updated'],
                'failed' => $stats['failed'],
            ]
        );

        // Dispatch следующей страницы
        if ($orderList->pagination->hasNext) {
            ImportOrdersJob::dispatch(
                page: $this->page + 1,
                limit: $this->limit,
                status: $this->status,
                documentType: $this->documentType,
                updatedAfter: $this->updatedAfter,
                includeDeleted: $this->includeDeleted
            );
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->logJobError(
            'Orders import job failed: '.$exception->getMessage(),
            ['page' => $this->page]
        );
    }
}
