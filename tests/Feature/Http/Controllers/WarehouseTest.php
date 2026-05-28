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
                    'warehouses' => [['id', 'name']],
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
    });
});
