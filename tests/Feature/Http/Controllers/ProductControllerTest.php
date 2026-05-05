<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature\Http\Controllers;

use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Tests\TestCase;
use Mockery;

class ProductControllerTest extends TestCase
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
    public function index_returns_paginated_products(): void
    {
        $mockResult = Mockery::mock(\stdClass::class);
        $mockResult->items = collect([]);
        $mockResult->shouldReceive('currentPage')->andReturn(1);
        $mockResult->shouldReceive('lastPage')->andReturn(1);
        $mockResult->shouldReceive('perPage')->andReturn(15);
        $mockResult->shouldReceive('total')->andReturn(0);
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($mockResult);
        $response = $this->getJson('/api/commercejson/products');
        $response->assertStatus(200)->assertJsonStructure(['data' => [], 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    /** @test */
    public function show_returns_product(): void
    {
        $productId = $this->createTestUuid();
        $product = Mockery::mock(Product::class)->makePartial();
        $product->shouldIgnoreMissing();
        $product->id = $productId;
        $product->name = 'Test Product';
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($product);
        $response = $this->getJson("/api/commercejson/products/{$productId}");
        $response->assertStatus(200)->assertJson(['id' => $productId, 'name' => 'Test Product']);
    }

    /** @test */
    public function store_creates_product(): void
    {
        $productId = $this->createTestUuid();
        $mockProduct = Mockery::mock(Product::class)->makePartial();
        $mockProduct->shouldIgnoreMissing();
        $mockProduct->id = $productId;
        $mockProduct->name = 'New Product';
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn($mockProduct);
        $response = $this->postJson('/api/commercejson/products', ['id' => $productId, 'name' => 'New Product', 'code' => 'NEW-001']);
        $response->assertStatus(201)->assertJson(['id' => $productId, 'name' => 'New Product']);
    }

    /** @test */
    public function update_updates_product(): void
    {
        $productId = $this->createTestUuid();
        $updateData = ['name' => 'Updated Product'];
        $product = Mockery::mock(Product::class)->makePartial();
        $product->shouldIgnoreMissing();
        $product->id = $productId;
        $mockUpdated = Mockery::mock(Product::class)->makePartial();
        $mockUpdated->shouldIgnoreMissing();
        $mockUpdated->id = $productId;
        $mockUpdated->name = $updateData['name'];
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($product);
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn($mockUpdated);
        $this->app->instance(QueryBusInterface::class, $this->mockQueryBus);
        $this->app->instance(CommandBusInterface::class, $this->mockCommandBus);
        $response = $this->putJson("/api/commercejson/products/{$productId}", $updateData);
        $response->assertStatus(200)->assertJson(['id' => $productId, 'name' => $updateData['name']]);
    }

    /** @test */
    public function destroy_deletes_product(): void
    {
        $productId = $this->createTestUuid();
        $product = Mockery::mock(Product::class)->makePartial();
        $product->shouldIgnoreMissing();
        $product->id = $productId;
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($product);
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn(true);
        $this->app->instance(QueryBusInterface::class, $this->mockQueryBus);
        $this->app->instance(CommandBusInterface::class, $this->mockCommandBus);
        $response = $this->deleteJson("/api/commercejson/products/{$productId}");
        $response->assertStatus(200)->assertJson(['message' => 'Product deleted']);
    }
}
