<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature\Http\Controllers;

use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Tests\TestCase;
use Mockery;

class OrderControllerTest extends TestCase
{
    protected Mockery\MockInterface|QueryBusInterface $mockQueryBus;

    protected Mockery\MockInterface|CommandBusInterface $mockCommandBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockQueryBus = Mockery::mock(QueryBusInterface::class);
        $this->mockCommandBus = Mockery::mock(CommandBusInterface::class);
        $this->app->instance(QueryBusInterface::class, $this->mockQueryBus);
        $this->app->instance(CommandBusInterface::class, $this->mockCommandBus);
        $this->app->instance(HttpClientInterface::class, Mockery::mock(HttpClientInterface::class));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function index_returns_paginated_orders(): void
    {
        $mockResult = Mockery::mock(\stdClass::class);
        $mockResult->items = collect([]);
        $mockResult->shouldReceive('currentPage')->andReturn(1);
        $mockResult->shouldReceive('lastPage')->andReturn(1);
        $mockResult->shouldReceive('perPage')->andReturn(15);
        $mockResult->shouldReceive('total')->andReturn(0);
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($mockResult);
        $response = $this->getJson('/api/commercejson/orders');
        $response->assertStatus(200)->assertJsonStructure(['data' => [], 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    /** @test */
    public function show_returns_order(): void
    {
        $orderId = $this->createTestUuid();
        $order = Mockery::mock(Order::class)->makePartial();
        $order->shouldIgnoreMissing();
        $order->id = $orderId;
        $order->number = 'ORD-001';
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($order);
        $response = $this->getJson("/api/commercejson/orders/{$orderId}");
        $response->assertStatus(200)->assertJson(['id' => $orderId]);
    }

    /** @test */
    public function store_creates_order(): void
    {
        $orderId = $this->createTestUuid();
        $mockOrder = Mockery::mock(Order::class)->makePartial();
        $mockOrder->shouldIgnoreMissing();
        $mockOrder->id = $orderId;
        $mockOrder->number = 'ORD-001';
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn($mockOrder);
        $response = $this->postJson('/api/commercejson/orders', ['id' => $orderId, 'number' => 'ORD-001']);
        $response->assertStatus(201)->assertJson(['id' => $orderId]);
    }

    /** @test */
    public function update_updates_order(): void
    {
        $orderId = $this->createTestUuid();
        $updateData = ['status' => 'confirmed'];
        $order = Mockery::mock(Order::class)->makePartial();
        $order->shouldIgnoreMissing();
        $order->id = $orderId;
        $mockUpdated = Mockery::mock(Order::class)->makePartial();
        $mockUpdated->shouldIgnoreMissing();
        $mockUpdated->id = $orderId;
        $mockUpdated->status = $updateData['status'];
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($order);
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn($mockUpdated);
        $this->app->instance(QueryBusInterface::class, $this->mockQueryBus);
        $this->app->instance(CommandBusInterface::class, $this->mockCommandBus);
        $response = $this->putJson("/api/commercejson/orders/{$orderId}", $updateData);
        $response->assertStatus(200)->assertJson(['id' => $orderId, 'status' => $updateData['status']]);
    }

    /** @test */
    public function destroy_deletes_order(): void
    {
        $orderId = $this->createTestUuid();
        $order = Mockery::mock(Order::class)->makePartial();
        $order->shouldIgnoreMissing();
        $order->id = $orderId;
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($order);
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn(true);
        $this->app->instance(QueryBusInterface::class, $this->mockQueryBus);
        $this->app->instance(CommandBusInterface::class, $this->mockCommandBus);
        $response = $this->deleteJson("/api/commercejson/orders/{$orderId}");
        $response->assertStatus(200)->assertJson(['message' => 'Order deleted']);
    }
}
