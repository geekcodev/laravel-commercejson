<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Commands\UpsertOfferCommand;
use GeekCo\CommerceJson\Queries\GetOffersQuery;
use GeekCo\CommerceJson\Queries\GetPriceTypesQuery;

describe('OfferController', function () {
    describe('GET /offers', function () {
        it('returns paginated offers list', function () {
            $queryBus = mockQueryBus();
            $mockResult = Mockery::mock(stdClass::class);
            $mockResult->shouldReceive('items')->andReturn(collect([
                test()->createOfferData(),
                test()->createOfferData(),
            ]));
            $mockResult->shouldReceive('currentPage')->andReturn(1);
            $mockResult->shouldReceive('lastPage')->andReturn(1);
            $mockResult->shouldReceive('perPage')->andReturn(15);
            $mockResult->shouldReceive('total')->andReturn(2);

            $queryBus->shouldReceive('ask')
                ->once()
                ->with(Mockery::type(GetOffersQuery::class))
                ->andReturn($mockResult);

            $response = $this->getJson('/api/commercejson/offers');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [['product_id', 'prices']],
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ]);
        });
    });

    describe('POST /offers', function () {
        it('imports offers in batch and returns ImportResult', function () {
            $commandBus = mockCommandBus();
            $productId = test()->createTestUuid();

            $offerData = test()->createOfferData([
                'product_id' => $productId,
            ]);

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->with(Mockery::type(UpsertOfferCommand::class))
                ->andReturn($offerData);

            $response = $this->postJson('/api/commercejson/offers', [
                'offers' => [$offerData->toArray()],
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 1,
                    'errors' => [],
                ]);
        });

        it('reports import errors', function () {
            $commandBus = mockCommandBus();
            $productId = test()->createTestUuid();

            $offerData = test()->createOfferData([
                'product_id' => $productId,
            ]);

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->with(Mockery::type(UpsertOfferCommand::class))
                ->andThrow(new Exception('Something went wrong'));

            $response = $this->postJson('/api/commercejson/offers', [
                'offers' => [$offerData->toArray()],
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => false,
                    'processed' => 0,
                    'errors' => [['code' => 'INTERNAL_ERROR']],
                ]);
        });
    });

    describe('GET /offers/price-types', function () {
        it('returns list of price types', function () {
            $queryBus = mockQueryBus();
            $priceTypes = collect([
                test()->createPriceTypeData(),
                test()->createPriceTypeData(),
            ]);

            $queryBus->shouldReceive('ask')
                ->once()
                ->with(Mockery::type(GetPriceTypesQuery::class))
                ->andReturn($priceTypes);

            $response = $this->getJson('/api/commercejson/offers/price-types');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'price_types' => [['id', 'name', 'currency']],
                ]);
        });
    });
});
