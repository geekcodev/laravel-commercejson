<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\OrderCreateData;
use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Data\OrderImportData;
use GeekCo\CommerceJson\Data\OrderListData;
use GeekCo\CommerceJson\Events\OrderCreated;
use GeekCo\CommerceJson\Events\OrderUpdated;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;

/**
 * Сервис для работы с заказами
 */
class OrderService
{
    public function __construct(
        protected CommerceJsonConnector $connector
    ) {}

    /**
     * Получить список заказов с пагинацией
     */
    public function getOrders(
        int $page = 1,
        int $limit = 100,
        ?string $status = null,
        ?string $documentType = null,
        ?\DateTime $updatedAfter = null,
        bool $includeDeleted = false
    ): OrderListData {
        $query = array_filter([
            'page' => $page,
            'limit' => $limit,
            'status' => $status,
            'document_type' => $documentType,
            'updated_after' => $updatedAfter?->format(\DateTime::ATOM),
            'include_deleted' => $includeDeleted ? 'true' : 'false',
        ]);

        $response = $this->connector->get('/orders', $query);

        return OrderListData::from($response->json());
    }

    /**
     * Получить заказ по ID
     */
    public function getOrder(string $id): OrderData
    {
        $response = $this->connector->get("/orders/{$id}");

        return OrderData::from($response->json());
    }

    /**
     * Создать новый заказ
     */
    public function createOrder(OrderCreateData $order, ?string $idempotencyKey = null): OrderData
    {
        $response = $this->connector->post(
            '/orders',
            $order->toArray(),
            $idempotencyKey
        );

        // Dispatch event
        event(new OrderCreated($response->json('id')));

        return OrderData::from($response->json());
    }

    /**
     * Обновить заказ (PATCH)
     *
     * @param  array<string, mixed>  $data
     */
    public function updateOrder(string $id, array $data, ?string $idempotencyKey = null): OrderData
    {
        $response = $this->connector->patch(
            "/orders/{$id}",
            $data,
            $idempotencyKey
        );

        // Dispatch event
        event(new OrderUpdated($id));

        return OrderData::from($response->json());
    }

    /**
     * Пакетный импорт заказов (ERP → сайт)
     */
    public function importOrders(OrderImportData $importData, ?string $idempotencyKey = null): ImportResultData
    {
        $response = $this->connector->post(
            '/orders/bulk',
            $importData->toArray(),
            $idempotencyKey
        );

        return ImportResultData::from($response->json());
    }

    /**
     * Обновить статус заказа
     */
    public function updateOrderStatus(string $id, string $status): OrderData
    {
        return $this->updateOrder($id, ['status' => $status]);
    }

    /**
     * Получить новые заказы (для экспорта в ERP)
     */
    public function getNewOrdersForExport(int $limit = 50): OrderListData
    {
        return $this->getOrders(
            page: 1,
            limit: $limit,
            status: 'new'
        );
    }

    /**
     * Получить заказы для выгрузки по дате обновления
     */
    public function getOrdersForIncrementalExport(\DateTime $since, int $limit = 100): OrderListData
    {
        return $this->getOrders(
            page: 1,
            limit: $limit,
            updatedAfter: $since
        );
    }
}
