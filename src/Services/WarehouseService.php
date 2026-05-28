<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use GeekCo\CommerceJson\Commands\UpsertWarehouseCommand;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\WarehouseData;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use Illuminate\Contracts\Bus\Dispatcher;

/**
 * Сервис для работы со складами
 */
class WarehouseService implements ServiceInterface
{
    public function __construct(
        protected HttpClientInterface $http,
        protected Dispatcher $commandBus
    ) {}

    public function setHttpClient(HttpClientInterface $http): static
    {
        $this->http = $http;

        return $this;
    }

    public function getHttpClient(): HttpClientInterface
    {
        return $this->http;
    }

    public function getCommandBus(): Dispatcher
    {
        return $this->commandBus;
    }

    /**
     * Получить список складов
     *
     * @return array<int, array>
     */
    public function getWarehouses(bool $includeDeleted = false): array
    {
        $query = $includeDeleted ? ['include_deleted' => 'true'] : [];

        $configPath = config('commercejson.external_api_endpoints.warehouses', '/warehouses');
        $response = $this->http->get($configPath, $query);

        return $response->data['warehouses'];
    }

    /**
     * Импортировать склады
     *
     * @param  array<int, array>  $warehouses
     */
    public function importWarehouses(array $warehouses, ?string $idempotencyKey = null): ImportResultData
    {
        $configPath = config('commercejson.external_api_endpoints.warehouses', '/warehouses');
        $response = $this->http->post(
            $configPath,
            ['warehouses' => $warehouses],
            $idempotencyKey
        );

        return ImportResultData::from($response->data);
    }

    /**
     * Синхронизировать склады с локальной БД
     *
     * @param  array<int, array>  $warehouses
     * @return int количество синхронизированных складов
     */
    public function syncWarehouses(array $warehouses): int
    {
        $count = 0;

        foreach ($warehouses as $warehouseData) {
            $this->commandBus->dispatch(new UpsertWarehouseCommand(WarehouseData::from($warehouseData)));

            $count++;
        }

        return $count;
    }
}
