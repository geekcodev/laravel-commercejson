<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature\Http\Controllers;

use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Models\Category;
use GeekCo\CommerceJson\Tests\TestCase;
use Mockery;

class CategoryControllerTest extends TestCase
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
    public function index_returns_paginated_categories(): void
    {
        $mockResult = Mockery::mock(\stdClass::class);
        $mockResult->items = collect([]);
        $mockResult->shouldReceive('currentPage')->andReturn(1);
        $mockResult->shouldReceive('lastPage')->andReturn(1);
        $mockResult->shouldReceive('perPage')->andReturn(15);
        $mockResult->shouldReceive('total')->andReturn(0);
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($mockResult);
        $response = $this->getJson('/api/commercejson/categories');
        $response->assertStatus(200)->assertJsonStructure(['data' => [], 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    /** @test */
    public function show_returns_category(): void
    {
        $categoryId = $this->createTestUuid();
        $category = Mockery::mock(Category::class)->makePartial();
        $category->shouldIgnoreMissing();
        $category->id = $categoryId;
        $category->name = 'Test Category';
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($category);
        $response = $this->getJson("/api/commercejson/categories/{$categoryId}");
        $response->assertStatus(200)->assertJson(['id' => $categoryId, 'name' => 'Test Category']);
    }

    /** @test */
    public function store_creates_category(): void
    {
        $categoryId = $this->createTestUuid();
        $mockCategory = Mockery::mock(Category::class)->makePartial();
        $mockCategory->shouldIgnoreMissing();
        $mockCategory->id = $categoryId;
        $mockCategory->name = 'New Category';
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn($mockCategory);
        $response = $this->postJson('/api/commercejson/categories', ['id' => $categoryId, 'name' => 'New Category']);
        $response->assertStatus(201)->assertJson(['id' => $categoryId, 'name' => 'New Category']);
    }

    /** @test */
    public function update_updates_category(): void
    {
        $categoryId = $this->createTestUuid();
        $updateData = ['name' => 'Updated'];
        $category = Mockery::mock(Category::class)->makePartial();
        $category->shouldIgnoreMissing();
        $category->id = $categoryId;
        $mockUpdated = Mockery::mock(Category::class)->makePartial();
        $mockUpdated->shouldIgnoreMissing();
        $mockUpdated->id = $categoryId;
        $mockUpdated->name = $updateData['name'];
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($category);
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn($mockUpdated);
        $this->app->instance(QueryBusInterface::class, $this->mockQueryBus);
        $this->app->instance(CommandBusInterface::class, $this->mockCommandBus);
        $response = $this->putJson("/api/commercejson/categories/{$categoryId}", $updateData);
        $response->assertStatus(200)->assertJson(['id' => $categoryId, 'name' => $updateData['name']]);
    }

    /** @test */
    public function destroy_deletes_category(): void
    {
        $categoryId = $this->createTestUuid();
        $category = Mockery::mock(Category::class)->makePartial();
        $category->shouldIgnoreMissing();
        $category->id = $categoryId;
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($category);
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn(true);
        $this->app->instance(QueryBusInterface::class, $this->mockQueryBus);
        $this->app->instance(CommandBusInterface::class, $this->mockCommandBus);
        $response = $this->deleteJson("/api/commercejson/categories/{$categoryId}");
        $response->assertStatus(200)->assertJson(['message' => 'Category deleted']);
    }
}
