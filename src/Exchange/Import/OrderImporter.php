<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exchange\Import;

use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Services\OrderService;

/**
 * Импортер заказов
 */
class OrderImporter
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Импортировать заказы
     *
     * @return array{imported: int, failed: int}
     */
    public function import(?\DateTime $updatedAfter = null): array
    {
        $stats = ['imported' => 0, 'failed' => 0];

        $page = 1;
        do {
            $orderList = $this->orderService->getOrders(
                page: $page,
                limit: 50,
                updatedAfter: $updatedAfter
            );

            foreach ($orderList->orders as $orderData) {
                try {
                    $this->syncOrder($orderData);
                    $stats['imported']++;
                } catch (\Exception $e) {
                    $stats['failed']++;
                    logger()->error("Failed to import order {$orderData->id}: ".$e->getMessage());
                }
            }

            $page++;
        } while ($orderList->pagination->hasNext);

        return $stats;
    }

    /**
     * Синхронизировать заказ с БД
     */
    protected function syncOrder(OrderData $orderData): Order
    {
        return Order::updateOrCreate(
            ['id' => $orderData->id],
            [
                'number' => $orderData->number,
                'external_id' => $orderData->externalId,
                'status' => $orderData->status->value,
                'document_type' => $orderData->documentType?->value ?? 'order',
                'counterparty_id' => $orderData->counterpartyId,
                'warehouse_id' => $orderData->warehouseId,
                'totals_total_amount' => $orderData->totals->total->amount,
                'totals_total_currency' => $orderData->totals->total->currency,
            ]
        );
    }
}
