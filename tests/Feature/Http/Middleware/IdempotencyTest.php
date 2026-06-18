<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Commands\CreateOrderCommand;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\OrderItem;

describe('IdempotencyMiddleware', function () {
    it('caches response by X-Idempotency-Key and returns cached on repeat', function () {
        $commandBus = mockCommandBus();

        $order = Order::factory()->make();
        $orderItem = OrderItem::factory()->make(['order_id' => $order->id]);
        $order->setRelation('items', collect([$orderItem]));

        $commandBus->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(CreateOrderCommand::class))
            ->andReturn($order);

        $productId = test()->createTestUuid();

        $payload = [
            'document_type' => DocumentTypeEnum::Order->value,
            'items' => [
                [
                    'product_id' => $productId,
                    'quantity' => 1,
                ],
            ],
        ];

        $idempotencyKey = 'test-key-'.bin2hex(random_bytes(8));

        $response1 = $this->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/commercejson/orders', $payload);

        $response1->assertStatus(201);
        $response1->assertJson(['id' => $order->id]);

        // Repeat with same key — dispatch should NOT be called (cached)
        $response2 = $this->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/commercejson/orders', $payload);

        $response2->assertStatus(201);
        $response2->assertJson(['id' => $order->id]);
        expect($response2->json())->toEqual($response1->json());
    });

    it('does not interfere with different idempotency keys', function () {
        $commandBus = mockCommandBus();

        $order1 = Order::factory()->make(['status' => OrderStatusEnum::New->value]);
        $orderItem1 = OrderItem::factory()->make(['order_id' => $order1->id]);
        $order1->setRelation('items', collect([$orderItem1]));

        $order2 = Order::factory()->make(['status' => OrderStatusEnum::Confirmed->value]);
        $orderItem2 = OrderItem::factory()->make(['order_id' => $order2->id]);
        $order2->setRelation('items', collect([$orderItem2]));

        $commandBus->shouldReceive('dispatch')
            ->twice()
            ->andReturn($order1, $order2);

        $productId = test()->createTestUuid();
        $payload = [
            'document_type' => DocumentTypeEnum::Order->value,
            'items' => [
                ['product_id' => $productId, 'quantity' => 1],
            ],
        ];

        $response1 = $this->withHeaders(['X-Idempotency-Key' => 'key-a'])
            ->postJson('/api/commercejson/orders', $payload);
        $response1->assertStatus(201);

        $response2 = $this->withHeaders(['X-Idempotency-Key' => 'key-b'])
            ->postJson('/api/commercejson/orders', $payload);
        $response2->assertStatus(201);

        expect($response2->json('status'))->not->toEqual($response1->json('status'));
    });

    it('does not cache GET requests', function () {
        $queryBus = mockQueryBus();
        $mockResult = Mockery::mock(stdClass::class);
        $mockResult->shouldReceive('items')->andReturn(collect([]));
        $mockResult->shouldReceive('currentPage')->andReturn(1);
        $mockResult->shouldReceive('lastPage')->andReturn(1);
        $mockResult->shouldReceive('perPage')->andReturn(15);
        $mockResult->shouldReceive('total')->andReturn(0);

        $queryBus->shouldReceive('ask')
            ->twice()
            ->andReturn($mockResult);

        $response1 = $this->withHeaders(['X-Idempotency-Key' => 'get-key'])
            ->getJson('/api/commercejson/orders');

        $response1->assertStatus(200);

        // Second GET with same key — queryBus::ask should be called again (no cache for GET)
        $response2 = $this->withHeaders(['X-Idempotency-Key' => 'get-key'])
            ->getJson('/api/commercejson/orders');

        $response2->assertStatus(200);
        expect($response2->json())->toEqual($response1->json());
    });

    it('processes different body with same key as separate request', function () {
        $commandBus = mockCommandBus();

        $order = Order::factory()->make();
        $orderItem = OrderItem::factory()->make(['order_id' => $order->id]);
        $order->setRelation('items', collect([$orderItem]));

        $commandBus->shouldReceive('dispatch')
            ->once()
            ->andReturn($order);

        $productId = test()->createTestUuid();
        $idempotencyKey = 'conflict-key-'.bin2hex(random_bytes(4));

        $response1 = $this->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/commercejson/orders', [
                'document_type' => DocumentTypeEnum::Order->value,
                'items' => [['product_id' => $productId, 'quantity' => 1]],
            ]);
        $response1->assertStatus(201);

        // Same key, different body — fingerprint won't match, so it's a new request
        // The middleware will process it normally (no cache hit because fingerprint differs)
        // and the handler will be called again. Not a 409, just normal operation.
        $order2 = Order::factory()->make(['status' => OrderStatusEnum::Confirmed->value]);
        $orderItem2 = OrderItem::factory()->make(['order_id' => $order2->id]);
        $order2->setRelation('items', collect([$orderItem2]));

        $commandBus->shouldReceive('dispatch')
            ->once()
            ->andReturn($order2);

        $response2 = $this->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/commercejson/orders', [
                'document_type' => DocumentTypeEnum::Order->value,
                'items' => [['product_id' => $productId, 'quantity' => 2]],
            ]);

        $response2->assertStatus(201);
        expect($response2->json('status'))->toEqual(OrderStatusEnum::Confirmed->value);
    });

    it('does not cache error responses (5xx)', function () {
        $commandBus = mockCommandBus();
        $productId = test()->createTestUuid();
        $idempotencyKey = 'error-key-'.bin2hex(random_bytes(4));

        $commandBus->shouldReceive('dispatch')
            ->twice()
            ->andThrow(new RuntimeException('Internal error'));

        $payload = [
            'document_type' => DocumentTypeEnum::Order->value,
            'items' => [['product_id' => $productId, 'quantity' => 1]],
        ];

        $response1 = $this->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/commercejson/orders', $payload);
        $response1->assertStatus(500);

        // Second request with same key — should NOT be cached (5xx is not cached)
        $response2 = $this->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/commercejson/orders', $payload);
        $response2->assertStatus(500);
    });

    it('works without X-Idempotency-Key header (no caching)', function () {
        $commandBus = mockCommandBus();

        $order1 = Order::factory()->make();
        $orderItem1 = OrderItem::factory()->make(['order_id' => $order1->id]);
        $order1->setRelation('items', collect([$orderItem1]));

        $order2 = Order::factory()->make(['status' => OrderStatusEnum::Confirmed->value]);
        $orderItem2 = OrderItem::factory()->make(['order_id' => $order2->id]);
        $order2->setRelation('items', collect([$orderItem2]));

        $commandBus->shouldReceive('dispatch')
            ->twice()
            ->andReturn($order1, $order2);

        $productId = test()->createTestUuid();
        $payload = [
            'document_type' => DocumentTypeEnum::Order->value,
            'items' => [['product_id' => $productId, 'quantity' => 1]],
        ];

        $response1 = $this->postJson('/api/commercejson/orders', $payload);
        $response1->assertStatus(201);

        // Same payload, no key — dispatch called again
        $response2 = $this->postJson('/api/commercejson/orders', $payload);
        $response2->assertStatus(201);

        expect($response2->json('status'))->toEqual(OrderStatusEnum::Confirmed->value);
    });
});
