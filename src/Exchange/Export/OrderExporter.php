<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exchange\Export;

use GeekCo\CommerceJson\Data\OrderImportData;
use GeekCo\CommerceJson\Services\OrderService;

/**
 * Экспортер заказов
 */
class OrderExporter
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Экспортировать новые заказы
     *
     * @return array{exported: int, failed: int}
     */
    public function export(int $limit = 50): array
    {
        $orderList = $this->orderService->getNewOrdersForExport($limit);

        return $this->doExport($orderList->orders);
    }

    /**
     * Экспортировать заказы по дате обновления
     *
     * @return array{exported: int, failed: int}
     */
    public function exportSince(\DateTime $since, int $limit = 50): array
    {
        $orderList = $this->orderService->getOrdersForIncrementalExport($since, $limit);

        return $this->doExport($orderList->orders);
    }

    /**
     * Выполнить экспорт заказов
     *
     * @return array{exported: int, failed: int}
     */
    protected function doExport(array $orders): array
    {
        $stats = ['exported' => 0, 'failed' => 0];

        $exportData = [];
        foreach ($orders as $order) {
            $exportData[] = [
                'id' => $order->id,
                'external_id' => $order->external_id,
                'status' => $order->status,
                'document_type' => $order->document_type,
                'comment' => $order->comment,
            ];
        }

        if (! empty($exportData)) {
            $result = $this->orderService->importOrders(
                OrderImportData::from(['orders' => $exportData])
            );

            $stats['exported'] = $result->processed;

            if (! empty($result->errors)) {
                $stats['failed'] = count($result->errors);
                foreach ($result->errors as $error) {
                    logger()->error("Export error: {$error->message}");
                }
            }
        }

        return $stats;
    }
}
