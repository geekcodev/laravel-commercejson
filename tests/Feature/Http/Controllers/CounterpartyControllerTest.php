<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature\Http\Controllers;

use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Tests\TestCase;
use Mockery;

class CounterpartyControllerTest extends TestCase
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
    public function index_returns_paginated_counterparties(): void
    {
        $mockResult = Mockery::mock(\stdClass::class);
        $mockResult->items = collect([]);
        $mockResult->shouldReceive('currentPage')->andReturn(1);
        $mockResult->shouldReceive('lastPage')->andReturn(1);
        $mockResult->shouldReceive('perPage')->andReturn(15);
        $mockResult->shouldReceive('total')->andReturn(0);
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($mockResult);
        $response = $this->getJson('/api/commercejson/counterparties');
        $response->assertStatus(200)->assertJsonStructure(['data' => [], 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    /** @test */
    public function show_returns_counterparty(): void
    {
        $id = $this->createTestUuid();
        $model = Mockery::mock(Counterparty::class)->makePartial();
        $model->shouldIgnoreMissing();
        $model->id = $id;
        $model->name = 'Test Company';
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($model);
        $response = $this->getJson("/api/commercejson/counterparties/{$id}");
        $response->assertStatus(200)->assertJson(['id' => $id, 'name' => 'Test Company']);
    }

    /** @test */
    public function store_creates_counterparty(): void
    {
        $id = $this->createTestUuid();
        $mock = Mockery::mock(Counterparty::class)->makePartial();
        $mock->shouldIgnoreMissing();
        $mock->id = $id;
        $mock->name = 'Test Company';
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn($mock);
        $response = $this->postJson('/api/commercejson/counterparties', ['id' => $id, 'name' => 'Test Company']);
        $response->assertStatus(201)->assertJson(['id' => $id, 'name' => 'Test Company']);
    }

    /** @test */
    public function update_updates_counterparty(): void
    {
        $id = $this->createTestUuid();
        $updateData = ['name' => 'Updated'];
        $model = Mockery::mock(Counterparty::class)->makePartial();
        $model->shouldIgnoreMissing();
        $model->id = $id;
        $mockUpdated = Mockery::mock(Counterparty::class)->makePartial();
        $mockUpdated->shouldIgnoreMissing();
        $mockUpdated->id = $id;
        $mockUpdated->name = $updateData['name'];
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($model);
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn($mockUpdated);
        $this->app->instance(QueryBusInterface::class, $this->mockQueryBus);
        $this->app->instance(CommandBusInterface::class, $this->mockCommandBus);
        $response = $this->putJson("/api/commercejson/counterparties/{$id}", $updateData);
        $response->assertStatus(200)->assertJson(['id' => $id, 'name' => $updateData['name']]);
    }

    /** @test */
    public function destroy_deletes_counterparty(): void
    {
        $id = $this->createTestUuid();
        $model = Mockery::mock(Counterparty::class)->makePartial();
        $model->shouldIgnoreMissing();
        $model->id = $id;
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($model);
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn(true);
        $this->app->instance(QueryBusInterface::class, $this->mockQueryBus);
        $this->app->instance(CommandBusInterface::class, $this->mockCommandBus);
        $response = $this->deleteJson("/api/commercejson/counterparties/{$id}");
        $response->assertStatus(200)->assertJson(['message' => 'Counterparty deleted']);
    }
}
