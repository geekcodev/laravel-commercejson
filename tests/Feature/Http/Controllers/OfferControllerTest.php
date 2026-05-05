<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature\Http\Controllers;

use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Models\Offer;
use GeekCo\CommerceJson\Tests\TestCase;
use Mockery;

class OfferControllerTest extends TestCase
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
    public function index_returns_paginated_offers(): void
    {
        $mockResult = Mockery::mock(\stdClass::class);
        $mockResult->items = collect([]);
        $mockResult->shouldReceive('currentPage')->andReturn(1);
        $mockResult->shouldReceive('lastPage')->andReturn(1);
        $mockResult->shouldReceive('perPage')->andReturn(15);
        $mockResult->shouldReceive('total')->andReturn(0);
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($mockResult);
        $response = $this->getJson('/api/commercejson/offers');
        $response->assertStatus(200)->assertJsonStructure(['data' => [], 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    /** @test */
    public function show_returns_offer(): void
    {
        $offerId = $this->createTestUuid();
        $offer = Mockery::mock(Offer::class)->makePartial();
        $offer->shouldIgnoreMissing();
        $offer->id = $offerId;
        $offer->price = 100.00;
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($offer);
        $response = $this->getJson("/api/commercejson/offers/{$offerId}");
        $response->assertStatus(200)->assertJson(['id' => $offerId]);
    }

    /** @test */
    public function store_creates_offer(): void
    {
        $offerId = $this->createTestUuid();
        $mockOffer = Mockery::mock(Offer::class)->makePartial();
        $mockOffer->shouldIgnoreMissing();
        $mockOffer->id = $offerId;
        $mockOffer->price = 100.00;
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn($mockOffer);
        $response = $this->postJson('/api/commercejson/offers', ['id' => $offerId, 'price' => 100.00]);
        $response->assertStatus(201)->assertJson(['id' => $offerId]);
    }

    /** @test */
    public function update_updates_offer(): void
    {
        $offerId = $this->createTestUuid();
        $updateData = ['price' => 150.00];
        $offer = Mockery::mock(Offer::class)->makePartial();
        $offer->shouldIgnoreMissing();
        $offer->id = $offerId;
        $mockUpdated = Mockery::mock(Offer::class)->makePartial();
        $mockUpdated->shouldIgnoreMissing();
        $mockUpdated->id = $offerId;
        $mockUpdated->price = $updateData['price'];
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($offer);
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn($mockUpdated);
        $this->app->instance(QueryBusInterface::class, $this->mockQueryBus);
        $this->app->instance(CommandBusInterface::class, $this->mockCommandBus);
        $response = $this->putJson("/api/commercejson/offers/{$offerId}", $updateData);
        $response->assertStatus(200)->assertJson(['id' => $offerId, 'price' => $updateData['price']]);
    }

    /** @test */
    public function destroy_deletes_offer(): void
    {
        $offerId = $this->createTestUuid();
        $offer = Mockery::mock(Offer::class)->makePartial();
        $offer->shouldIgnoreMissing();
        $offer->id = $offerId;
        $this->mockQueryBus->shouldReceive('ask')->once()->andReturn($offer);
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn(true);
        $this->app->instance(QueryBusInterface::class, $this->mockQueryBus);
        $this->app->instance(CommandBusInterface::class, $this->mockCommandBus);
        $response = $this->deleteJson("/api/commercejson/offers/{$offerId}");
        $response->assertStatus(200)->assertJson(['message' => 'Offer deleted']);
    }
}
