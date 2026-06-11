<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Queries\GetCounterpartiesQuery;
use GeekCo\CommerceJson\Queries\GetCounterpartyQuery;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

describe('CounterpartyController', function () {
    describe('GET /counterparties', function () {
        it('returns paginated counterparties list', function () {
            $queryBus = mockQueryBus();
            $mockResult = Mockery::mock(stdClass::class);
            $mockResult->shouldReceive('items')->andReturn(collect([
                Counterparty::factory()->make([
                    'id' => test()->createTestUuid(),
                    'name' => 'Test Company',
                    'type' => 'legal_entity',
                    'inn' => '1234567890',
                ]),
            ]));
            $mockResult->shouldReceive('currentPage')->andReturn(1);
            $mockResult->shouldReceive('lastPage')->andReturn(1);
            $mockResult->shouldReceive('perPage')->andReturn(15);
            $mockResult->shouldReceive('total')->andReturn(1);

            $queryBus->shouldReceive('ask')
                ->once()
                ->with(Mockery::type(GetCounterpartiesQuery::class))
                ->andReturn($mockResult);

            $response = $this->getJson('/api/commercejson/counterparties');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'counterparties' => [['id', 'name', 'type']],
                    'pagination' => ['page', 'limit', 'total', 'has_next'],
                ]);
        });
    });

    describe('POST /counterparties', function () {
        it('creates a counterparty and returns 201', function () {
            $commandBus = mockCommandBus();
            $id = test()->createTestUuid();
            $model = Counterparty::factory()->make([
                'id' => $id,
                'name' => 'Test Company',
                'type' => 'legal_entity',
                'inn' => '1234567890',
            ]);

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->andReturn($model);

            $response = $this->postJson('/api/commercejson/counterparties', [
                'id' => $id,
                'name' => 'Test Company',
                'type' => 'legal_entity',
                'inn' => '1234567890',
            ]);

            $response->assertStatus(201)
                ->assertJson([
                    'id' => $id,
                    'name' => 'Test Company',
                ]);
        });

        it('returns 422 on foreign key violation', function () {
            $commandBus = mockCommandBus();

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->andThrow(new QueryException(
                    'mysql',
                    'INSERT INTO counterparties (...) VALUES (...)',
                    [],
                    new Exception('SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row')
                ));

            $response = $this->postJson('/api/commercejson/counterparties', [
                'id' => test()->createTestUuid(),
                'name' => 'Test',
                'type' => 'legal_entity',
                'price_type_id' => test()->createTestUuid(),
            ]);

            $response->assertStatus(422)
                ->assertJsonStructure([
                    'error' => ['code', 'message'],
                ])
                ->assertJson([
                    'error' => ['code' => 'FOREIGN_KEY_VIOLATION'],
                ]);
        });
    });

    describe('GET /counterparties/{id}', function () {
        it('returns a single counterparty', function () {
            $queryBus = mockQueryBus();
            $id = test()->createTestUuid();
            $model = Counterparty::factory()->make([
                'id' => $id,
                'name' => 'Test Company',
                'type' => 'legal_entity',
            ]);

            $queryBus->shouldReceive('ask')
                ->once()
                ->with(Mockery::type(GetCounterpartyQuery::class))
                ->andReturn($model);

            $response = $this->getJson("/api/commercejson/counterparties/{$id}");

            $response->assertStatus(200)
                ->assertJson([
                    'id' => $id,
                    'name' => 'Test Company',
                ]);
        });

        it('returns 404 when counterparty not found', function () {
            $queryBus = mockQueryBus();
            $id = test()->createTestUuid();

            $queryBus->shouldReceive('ask')
                ->once()
                ->with(Mockery::type(GetCounterpartyQuery::class))
                ->andThrow(new ModelNotFoundException);

            $response = $this->getJson("/api/commercejson/counterparties/{$id}");

            $response->assertStatus(404)
                ->assertJson([
                    'error' => ['code' => 'NOT_FOUND', 'message' => 'Counterparty not found'],
                ]);
        });
    });
});
