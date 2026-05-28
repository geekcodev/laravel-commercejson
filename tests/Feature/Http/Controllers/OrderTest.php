<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Commands\CreateOrderCommand;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Queries\GetOrderQuery;
use GeekCo\CommerceJson\Queries\GetOrdersQuery;

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
                    'data' => [['id', 'number', 'status']],
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
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
                        'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                        'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    ],
                ],
                'totals' => [
                    'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
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
            $queryBus = mockQueryBus();
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

            $queryBus->shouldReceive('ask')
                ->once()
                ->andReturn($order);
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
