<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Queries\GetWarehousesQuery;

describe('WarehouseController', function () {
    describe('GET /warehouses', function () {
        it('returns warehouses list', function () {
            $queryBus = mockQueryBus();
            $warehouses = collect([
                test()->createWarehouseData(),
                test()->createWarehouseData(),
            ]);

            $queryBus->shouldReceive('ask')
                ->once()
                ->with(Mockery::type(GetWarehousesQuery::class))
                ->andReturn($warehouses);

            $response = $this->getJson('/api/commercejson/warehouses');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'warehouses' => [['id', 'name', 'is_partner', 'can_cancel_order', 'delivery_time']],
                ]);
        });
    });

    describe('POST /warehouses', function () {
        it('imports warehouses successfully', function () {
            $commandBus = mockCommandBus();

            $commandBus->shouldReceive('dispatch')
                ->times(2)
                ->andReturn(true, true);

            $response = $this->postJson('/api/commercejson/warehouses', [
                'warehouses' => [
                    ['id' => test()->createTestUuid(), 'name' => 'Warehouse A'],
                    ['id' => test()->createTestUuid(), 'name' => 'Warehouse B'],
                ],
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 2,
                    'errors' => [],
                ]);
        });

        it('reports import errors', function () {
            $commandBus = mockCommandBus();

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->andThrow(new RuntimeException('Duplicate warehouse'));

            $response = $this->postJson('/api/commercejson/warehouses', [
                'warehouses' => [
                    ['id' => test()->createTestUuid(), 'name' => 'Duplicate'],
                ],
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => false,
                    'processed' => 0,
                ]);
        });

        it('accepts delivery_time field', function () {
            $commandBus = mockCommandBus();

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->with(Mockery::on(function ($command) {
                    return $command->warehouseData->delivery_time === '1-3 дня';
                }))
                ->andReturn(null);

            $this->postJson('/api/commercejson/warehouses', [
                'warehouses' => [
                    [
                        'id' => test()->createTestUuid(),
                        'name' => 'Fast WH',
                        'delivery_time' => '1-3 дня',
                    ],
                ],
            ])->assertStatus(200);
        });

        it('accepts is_partner and can_cancel_order fields', function () {
            $commandBus = mockCommandBus();

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->with(Mockery::on(function ($command) {
                    return $command->warehouseData->is_partner === true
                        && $command->warehouseData->can_cancel_order === false;
                }))
                ->andReturn(null);

            $this->postJson('/api/commercejson/warehouses', [
                'warehouses' => [
                    [
                        'id' => test()->createTestUuid(),
                        'name' => 'Partner WH',
                        'is_partner' => true,
                        'can_cancel_order' => false,
                    ],
                ],
            ])->assertStatus(200);
        });
    });
});
