<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use DateTime;
use DateTimeInterface;
use GeekCo\CommerceJson\Commands\UpsertOrderCommand;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\OrderCreateData;
use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Data\OrderImportData;
use GeekCo\CommerceJson\Data\OrderListData;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Events\OrderCreated;
use GeekCo\CommerceJson\Events\OrderUpdated;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Models\Order;
use Illuminate\Contracts\Bus\Dispatcher;

/**
 * Сервис для работы с заказами
 */
class OrderService implements ServiceInterface
{
    public function __construct(
        protected HttpClientInterface $http,
        protected Dispatcher $commandBus
    ) {}

    /**
     * {@inheritDoc}
     */
    public function setHttpClient(HttpClientInterface $http): static
    {
        $this->http = $http;

        return $this;
    }

    /**
     * Получить HTTP клиент
     */
    public function getHttpClient(): HttpClientInterface
    {
        return $this->http;
    }

    /**
     * Получить CommandBus
     */
    public function getCommandBus(): Dispatcher
    {
        return $this->commandBus;
    }

    /**
     * Получить список заказов с пагинацией
     */
    public function getOrders(
        int $page = 1,
        int $limit = 100,
        ?string $status = null,
        ?string $documentType = null,
        ?DateTime $updatedAfter = null,
        bool $includeDeleted = false
    ): OrderListData {
        $query = array_filter([
            'page' => $page,
            'limit' => $limit,
            'status' => $status,
            'document_type' => $documentType,
            'updated_after' => $updatedAfter?->format(DateTimeInterface::ATOM),
            'include_deleted' => $includeDeleted ? 'true' : 'false',
        ]);

        $configPath = config('commercejson.external_api_endpoints.orders', '/orders');
        $response = $this->http->get($configPath, $query);

        return OrderListData::from($response->data);
    }

    /**
     * Получить заказ по ID
     */
    public function getOrder(string $id): OrderData
    {
        $configPath = config('commercejson.external_api_endpoints.orders', '/orders');
        $response = $this->http->get("{$configPath}/{$id}");

        return OrderData::from($response->data);
    }

    /**
     * Создать новый заказ
     */
    public function createOrder(OrderCreateData $order, ?string $idempotencyKey = null): OrderData
    {
        $configPath = config('commercejson.external_api_endpoints.orders', '/orders');
        $response = $this->http->post(
            $configPath,
            $order->toArray(),
            $idempotencyKey
        );

        $orderData = OrderData::from($response->data);

        // Dispatch event
        event(new OrderCreated($response->data['id']));

        return $orderData;
    }

    /**
     * Обновить заказ (PATCH)
     *
     * @param  array<string, mixed>  $data
     */
    public function updateOrder(string $id, array $data, ?string $idempotencyKey = null): OrderData
    {
        $configPath = config('commercejson.external_api_endpoints.orders', '/orders');
        $response = $this->http->patch(
            "{$configPath}/{$id}",
            $data,
            $idempotencyKey
        );

        // Dispatch event
        event(new OrderUpdated($id));

        return OrderData::from($response->data);
    }

    /**
     * Пакетный импорт заказов (ERP → сайт)
     */
    public function importOrders(OrderImportData $importData, ?string $idempotencyKey = null): ImportResultData
    {
        $configPath = config('commercejson.external_api_endpoints.orders_bulk', '/orders/bulk');
        $response = $this->http->post(
            $configPath,
            $importData->toArray(),
            $idempotencyKey
        );

        return ImportResultData::from($response->data);
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
            status: OrderStatusEnum::New->value
        );
    }

    /**
     * Получить заказы для выгрузки по дате обновления
     */
    public function getOrdersForIncrementalExport(DateTime $since, int $limit = 100): OrderListData
    {
        return $this->getOrders(
            page: 1,
            limit: $limit,
            updatedAfter: $since
        );
    }

    /**
     * Синхронизировать заказ с локальной БД
     */
    public function syncOrder(OrderData $orderData): Order
    {
        $command = new UpsertOrderCommand($orderData);

        return $this->commandBus->dispatch($command);
    }
}
