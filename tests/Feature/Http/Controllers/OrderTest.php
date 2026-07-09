<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Commands\BulkUpsertOrderCommand;
use GeekCo\CommerceJson\Commands\CreateOrderCommand;
use GeekCo\CommerceJson\Commands\PatchOrderCommand;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\OrderItem;
use GeekCo\CommerceJson\Queries\GetOrderQuery;
use GeekCo\CommerceJson\Queries\GetOrdersQuery;
use Illuminate\Database\QueryException;

describe('OrderController', function () {
    describe('GET /orders', function () {
        it('returns paginated orders list', function () {
            $queryBus = mockQueryBus();

            $order = Order::factory()->make();
            $orderItem = OrderItem::factory()->make(['order_id' => $order->id]);
            $order->setRelation('items', collect([$orderItem]));

            $mockResult = Mockery::mock(stdClass::class);
            $mockResult->shouldReceive('items')->andReturn([$order]);
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

            $order = Order::factory()->make();
            $orderItem = OrderItem::factory()->make([
                'order_id' => $order->id,
                'product_id' => $productId,
                'price_amount' => 100.00,
                'total_amount' => 100.00,
            ]);
            $order->setRelation('items', collect([$orderItem]));

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->with(Mockery::type(CreateOrderCommand::class))
                ->andReturn($order);

            $response = $this->postJson('/api/commercejson/orders', [
                'document_type' => DocumentTypeEnum::Order->value,
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

        it('creates order with linked_documents', function () {
            $commandBus = mockCommandBus();
            $productId = test()->createTestUuid();
            $linkedOrderId = test()->createTestUuid();

            $order = Order::factory()->make();
            $orderItem = OrderItem::factory()->make([
                'order_id' => $order->id,
                'product_id' => $productId,
                'price_amount' => 100.00,
                'total_amount' => 100.00,
            ]);
            $order->setRelation('items', collect([$orderItem]));

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->with(Mockery::on(function (CreateOrderCommand $command) use ($linkedOrderId) {
                    expect($command->createData->linked_documents)->toHaveCount(1);
                    expect($command->createData->linked_documents[0]->id)->toBe($linkedOrderId);

                    return true;
                }))
                ->andReturn($order);

            $response = $this->postJson('/api/commercejson/orders', [
                'document_type' => DocumentTypeEnum::Order->value,
                'linked_documents' => [
                    ['id' => $linkedOrderId, 'type' => DocumentTypeEnum::Order->value],
                ],
                'items' => [
                    [
                        'product_id' => $productId,
                        'quantity' => 1,
                    ],
                ],
            ]);

            $response->assertStatus(201);
        });
    });

    describe('GET /orders/{id}', function () {
        it('returns a single order', function () {
            $queryBus = mockQueryBus();

            $order = Order::factory()->make();
            $orderItem = OrderItem::factory()->make(['order_id' => $order->id]);
            $order->setRelation('items', collect([$orderItem]));

            $queryBus->shouldReceive('ask')
                ->once()
                ->with(Mockery::type(GetOrderQuery::class))
                ->andReturn($order);

            $response = $this->getJson('/api/commercejson/orders/'.$order->id);

            $response->assertStatus(200)
                ->assertJson(['id' => $order->id]);
        });
    });

    describe('PATCH /orders/{id}', function () {
        it('updates an order', function () {
            $commandBus = mockCommandBus();
            $orderId = test()->createTestUuid();

            $order = Order::factory()->make([
                'id' => $orderId,
                'status' => OrderStatusEnum::Confirmed->value,
            ]);
            $orderItem = OrderItem::factory()->make(['order_id' => $order->id]);
            $order->setRelation('items', collect([$orderItem]));

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->andReturn($order);

            $response = $this->patchJson("/api/commercejson/orders/{$orderId}", [
                'id' => $orderId,
                'status' => OrderStatusEnum::Confirmed->value,
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
                    'status' => OrderStatusEnum::Confirmed->value,
                ]);
        });

        it('updates order with linked_documents', function () {
            $commandBus = mockCommandBus();
            $orderId = test()->createTestUuid();
            $linkedOrderId = test()->createTestUuid();

            $order = Order::factory()->make([
                'id' => $orderId,
                'status' => OrderStatusEnum::Confirmed->value,
            ]);
            $orderItem = OrderItem::factory()->make(['order_id' => $orderId]);
            $order->setRelation('items', collect([$orderItem]));

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->with(Mockery::on(function (PatchOrderCommand $command) use ($linkedOrderId) {
                    expect($command->patchData->linked_documents)->toHaveCount(1);
                    expect($command->patchData->linked_documents[0]->id)->toBe($linkedOrderId);

                    return true;
                }))
                ->andReturn($order);

            $response = $this->patchJson("/api/commercejson/orders/{$orderId}", [
                'id' => $orderId,
                'status' => OrderStatusEnum::Confirmed->value,
                'linked_documents' => [
                    ['id' => $linkedOrderId, 'type' => DocumentTypeEnum::Order->value],
                ],
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
                    'status' => OrderStatusEnum::Confirmed->value,
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
                'status' => OrderStatusEnum::Confirmed->value,
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
                        'status' => OrderStatusEnum::New->value,
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
                        'status' => OrderStatusEnum::New->value,
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
                        'status' => OrderStatusEnum::New->value,
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
                        'status' => OrderStatusEnum::New->value,
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

        it('imports order with linked_documents', function () {
            $commandBus = mockCommandBus();
            $linkedOrderId = test()->createTestUuid();

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->with(Mockery::on(function (BulkUpsertOrderCommand $command) use ($linkedOrderId) {
                    expect($command->linked_documents)->toHaveCount(1);
                    expect($command->linked_documents[0]->id)->toBe($linkedOrderId);

                    return true;
                }))
                ->andReturn(true);

            $response = $this->postJson('/api/commercejson/orders/bulk', [
                'orders' => [
                    [
                        'id' => test()->createTestUuid(),
                        'number' => 'ORD-LNK-001',
                        'status' => OrderStatusEnum::New->value,
                        'linked_documents' => [
                            ['id' => $linkedOrderId, 'type' => DocumentTypeEnum::Order->value],
                        ],
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
                    'processed' => 1,
                    'errors' => [],
                ]);
        });
    });
});
