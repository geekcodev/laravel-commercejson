<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Models\Warehouse;

/**
 * Сервис для работы со складами
 */
class WarehouseService
{
    public function __construct(
        protected CommerceJsonConnector $connector
    ) {}

    /**
     * Получить список складов
     *
     * @return array<int, array>
     */
    public function getWarehouses(bool $includeDeleted = false): array
    {
        $query = $includeDeleted ? ['include_deleted' => 'true'] : [];

        $response = $this->connector->get('/warehouses', $query);
        $responseData = json_decode($response->getBody()->getContents(), true);

        return $responseData['warehouses'];
    }

    /**
     * Импортировать склады
     *
     * @param  array<int, array>  $warehouses
     */
    public function importWarehouses(array $warehouses, ?string $idempotencyKey = null): ImportResultData
    {
        $response = $this->connector->post(
            '/warehouses',
            ['warehouses' => $warehouses],
            $idempotencyKey
        );

        return ImportResultData::from(json_decode($response->getBody()->getContents(), true));
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
            Warehouse::updateOrCreate(
                ['id' => $warehouseData['id']],
                [
                    'external_id' => $warehouseData['external_id'] ?? null,
                    'name' => $warehouseData['name'],
                    'code' => $warehouseData['code'] ?? null,
                    'address_country' => $warehouseData['address']['country'] ?? null,
                    'address_region' => $warehouseData['address']['region'] ?? null,
                    'address_district' => $warehouseData['address']['district'] ?? null,
                    'address_city' => $warehouseData['address']['city'] ?? null,
                    'address_street' => $warehouseData['address']['street'] ?? null,
                    'address_house' => $warehouseData['address']['house'] ?? null,
                    'address_building' => $warehouseData['address']['building'] ?? null,
                    'address_apartment' => $warehouseData['address']['apartment'] ?? null,
                    'address_postal_code' => $warehouseData['address']['postal_code'] ?? null,
                    'address_full' => $warehouseData['address']['full'] ?? null,
                    'is_active' => $warehouseData['is_active'] ?? true,
                    'is_default' => $warehouseData['is_default'] ?? false,
                ]
            );

            $count++;
        }

        return $count;
    }
}
