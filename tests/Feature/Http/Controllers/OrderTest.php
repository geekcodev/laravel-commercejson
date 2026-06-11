<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Commands\CreateOrderCommand;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Queries\GetOrderQuery;
use GeekCo\CommerceJson\Queries\GetOrdersQuery;
use Illuminate\Database\QueryException;

describe('OrderController', function () {
    describe('GET /orders', function () {
        it('returns paginated orders list', function () {
            $queryBus = mockQueryBus();
            $mockResult = Mockery::mock(stdClass::class);
            $mockResult->shouldReceive('items')->andReturn(collect([
                test()->createOrderData(),
            ]));
            $mockResult->shouldReceive('currentPage')->andReturn(1);
            $mockResult->shouldReceive('lastPage')->andReturn(1);
            $mockResult->shouldReceive('perPage')->andReturn(15);
            $mockResult->shouldReceive('total')->andReturn(1);

            $queryBus->shouldReceive('ask')
                ->once()
                ->with(Mockery::type(GetOrdersQuery::class))
                ->andReturn($mockResult);

            $response = $this->getJson('/api/commercejson/orders');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'orders' => [['id', 'number', 'status']],
                    'pagination' => ['page', 'limit', 'total', 'has_next'],
                ]);
        });
    });

    describe('POST /orders', function () {
        it('creates an order and returns 201', function () {
            $commandBus = mockCommandBus();
            $productId = test()->createTestUuid();

            $orderData = test()->createOrderData([
                'items' => [
                    [
                        'id' => test()->createTestUuid(),
                        'product_id' => $productId,
                        'quantity' => 1,
                        'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                        'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    ],
                ],
            ]);

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->with(Mockery::type(CreateOrderCommand::class))
                ->andReturn($orderData);

            $response = $this->postJson('/api/commercejson/orders', [
                'document_type' => 'order',
                'items' => [
                    [
                        'product_id' => $productId,
                        'quantity' => 1,
                    ],
                ],
            ]);

            $response->assertStatus(201);
            $this->assertArrayHasKey('id', $response->json());
        });
    });

    describe('GET /orders/{id}', function () {
        it('returns a single order', function () {
            $queryBus = mockQueryBus();

            $orderData = test()->createOrderData();

            $queryBus->shouldReceive('ask')
                ->once()
                ->with(Mockery::type(GetOrderQuery::class))
                ->andReturn($orderData);

            $response = $this->getJson('/api/commercejson/orders/'.$orderData->id);

            $response->assertStatus(200)
                ->assertJson(['id' => $orderData->id]);
        });
    });

    describe('PATCH /orders/{id}', function () {
        it('updates an order', function () {
            $commandBus = mockCommandBus();
            $orderId = test()->createTestUuid();
            $order = Order::factory()->make([
                'id' => $orderId,
                'number' => 'ORD-001',
                'status' => 'new',
            ]);

            $updatedOrderData = test()->createOrderData([
                'id' => $orderId,
                'status' => 'confirmed',
            ]);

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->andReturn($updatedOrderData);

            $response = $this->patchJson("/api/commercejson/orders/{$orderId}", [
                'id' => $orderId,
                'status' => 'confirmed',
                'items' => [
                    [
                        'id' => test()->createTestUuid(),
                        'product_id' => test()->createTestUuid(),
                        'quantity' => 1,
                        'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                        'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    ],
                ],
                'totals' => [
                    'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'id' => $orderId,
                    'status' => 'confirmed',
                ]);
        });

        it('returns 422 on foreign key violation', function () {
            $commandBus = mockCommandBus();
            $orderId = test()->createTestUuid();

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->andThrow(new QueryException(
                    'mysql',
                    'UPDATE orders SET ...',
                    [],
                    new Exception('SQLSTATE[23000]: Integrity constraint violation')
                ));

            $response = $this->patchJson("/api/commercejson/orders/{$orderId}", [
                'id' => $orderId,
                'status' => 'confirmed',
                'items' => [
                    [
                        'id' => test()->createTestUuid(),
                        'product_id' => test()->createTestUuid(),
                        'quantity' => 1,
                        'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                        'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    ],
                ],
                'totals' => [
                    'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ]);

            $response->assertStatus(422)
                ->assertJsonStructure([
                    'error' => ['code', 'message'],
                ]);
        });
    });

    describe('POST /orders/bulk', function () {
        it('imports orders in bulk', function () {
            $commandBus = mockCommandBus();

            $commandBus->shouldReceive('dispatch')
                ->times(2)
                ->andReturn(true, true);

            $response = $this->postJson('/api/commercejson/orders/bulk', [
                'orders' => [
                    [
                        'id' => test()->createTestUuid(),
                        'number' => 'ORD-001',
                        'status' => 'new',
                        'items' => [
                            [
                                'id' => test()->createTestUuid(),
                                'product_id' => test()->createTestUuid(),
                                'quantity' => 1,
                                'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                            ],
                        ],
                        'totals' => [
                            'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                            'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                        ],
                    ],
                    [
                        'id' => test()->createTestUuid(),
                        'number' => 'ORD-002',
                        'status' => 'new',
                        'items' => [
                            [
                                'id' => test()->createTestUuid(),
                                'product_id' => test()->createTestUuid(),
                                'quantity' => 1,
                                'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                            ],
                        ],
                        'totals' => [
                            'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                            'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                        ],
                    ],
                ],
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 2,
                    'errors' => [],
                ]);
        });

        it('handles delivery tracking in bulk import', function () {
            $commandBus = mockCommandBus();

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->andReturn(true);

            $response = $this->postJson('/api/commercejson/orders/bulk', [
                'orders' => [
                    [
                        'id' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                        'external_id' => 'string',
                        'status' => 'new',
                        'comment' => 'string',
                        'delivery' => [
                            'tracking_number' => 'string',
                            'shipped_at' => '2026-05-28T19:30:12.949Z',
                            'estimated_date' => '2026-05-28',
                        ],
                        'items' => [
                            [
                                'id' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                                'product_id' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                                'variant_id' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                                'quantity' => 1,
                                'price' => ['amount' => '1500.00', 'currency' => CurrencyEnum::RUB->value],
                            ],
                            [
                                'id' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                                'product_id' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                                'variant_id' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                                'quantity' => 1,
                                'price' => ['amount' => '1500.00', 'currency' => CurrencyEnum::RUB->value],
                            ],
                        ],
                        'custom_attributes' => [
                            ['key' => 'string', 'value' => 'string'],
                        ],
                    ],
                ],
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 1,
                    'errors' => [],
                ]);
        });

        it('reports errors in bulk import', function () {
            $commandBus = mockCommandBus();

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->andThrow(new RuntimeException('Order already exists'));

            $response = $this->postJson('/api/commercejson/orders/bulk', [
                'orders' => [
                    [
                        'id' => test()->createTestUuid(),
                        'number' => 'ORD-001',
                        'status' => 'new',
                        'items' => [
                            [
                                'id' => test()->createTestUuid(),
                                'product_id' => test()->createTestUuid(),
                                'quantity' => 1,
                                'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                            ],
                        ],
                        'totals' => [
                            'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                            'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                        ],
                    ],
                ],
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => false,
                    'processed' => 0,
                ]);
            expect($response->json('errors'))->toHaveCount(1);
        });
    });
});
