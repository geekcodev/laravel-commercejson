<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature;

use Carbon\Carbon;
use GeekCo\CommerceJson\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;

class OffersApiTest extends TestCase
{
    protected CommerceJsonConnector|MockInterface $mockConnector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConnector = Mockery::mock(CommerceJsonConnector::class);
        $this->app->instance(CommerceJsonConnector::class, $this->mockConnector);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function get_offers_returns_paginated_list(): void
    {
        $mockOffersData = [
            'offers' => [
                [
                    'product_id' => $this->createTestUuid(),
                    'prices' => [['price_type_id' => $this->createTestUuid(), 'price' => ['amount' => '100.00', 'currency' => 'RUB']]],
                    'updated_at' => now()->toIso8601String(),
                ],
            ],
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'has_next' => false],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/offers', ['page' => 1, 'limit' => 10])
            ->andReturn(new Response(200, [], json_encode($mockOffersData)));

        $response = $this->mockConnector->get('/offers', ['page' => 1, 'limit' => 10]);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $responseData['offers']);
        $this->assertEquals(1, $responseData['pagination']['page']);
        $this->assertValidUuid($responseData['offers'][0]['product_id']);
        $this->assertMoneyFormat($responseData['offers'][0]['prices'][0]['price']['amount']);
    }

    /** @test */
    public function get_offers_with_filters_and_incremental_sync(): void
    {
        $priceTypeId = $this->createTestUuid();
        $warehouseId = $this->createTestUuid();
        $updatedAfter = Carbon::now()->subDay()->toIso8601String();
        $mockOffersData = [
            'offers' => [
                [
                    'product_id' => $this->createTestUuid(),
                    'prices' => [['price_type_id' => $priceTypeId, 'price' => ['amount' => '150.00', 'currency' => 'RUB']]],
                    'stocks' => [['warehouse_id' => $warehouseId, 'quantity' => 5]],
                    'updated_at' => now()->toIso8601String(),
                ],
            ],
            'pagination' => ['page' => 1, 'limit' => 1, 'total' => 1, 'has_next' => false],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/offers', [
                'price_type_id' => $priceTypeId,
                'warehouse_id' => $warehouseId,
                'updated_after' => $updatedAfter,
                'include_deleted' => true,
            ])
            ->andReturn(new Response(200, [], json_encode($mockOffersData)));

        $response = $this->mockConnector->get('/offers', [
            'price_type_id' => $priceTypeId,
            'warehouse_id' => $warehouseId,
            'updated_after' => $updatedAfter,
            'include_deleted' => true,
        ]);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $responseData['offers']);
        $this->assertEquals($priceTypeId, $responseData['offers'][0]['prices'][0]['price_type_id']);
        $this->assertEquals($warehouseId, $responseData['offers'][0]['stocks'][0]['warehouse_id']);
        $this->assertIso8601Date($responseData['offers'][0]['updated_at']);
    }

    /** @test */
    public function post_offers_batch_upsert_success_and_idempotency(): void
    {
        $idempotencyKey = $this->createTestUuid();
        $priceTypeId = $this->createTestUuid();
        $warehouseId = $this->createTestUuid();
        $offersData = [
            'valid_from' => '2025-01-01',
            'offers' => [
                [
                    'product_id' => $this->createTestUuid(),
                    'prices' => [['price_type_id' => $priceTypeId, 'price' => ['amount' => '200.00', 'currency' => 'USD']]],
                    'stocks' => [['warehouse_id' => $warehouseId, 'quantity' => 10]],
                ],
            ],
        ];
        $mockImportResult = ['success' => true, 'processed' => 1, 'errors' => [], 'warnings' => []];

        // First call
        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/offers', $offersData, $idempotencyKey)
            ->andReturn(new Response(200, [], json_encode($mockImportResult)));

        $response1 = $this->mockConnector->post('/offers', $offersData, $idempotencyKey);
        $responseData1 = json_decode((string) $response1->getBody(), true);

        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertTrue($responseData1['success']);

        // Second call with the same idempotency key
        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/offers', $offersData, $idempotencyKey)
            ->andReturn(new Response(200, [], json_encode($mockImportResult)));

        $response2 = $this->mockConnector->post('/offers', $offersData, $idempotencyKey);
        $responseData2 = json_decode((string) $response2->getBody(), true);

        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertTrue($responseData2['success']);
        $this->assertEquals($responseData1, $responseData2);
    }

    /** @test */
    public function post_offers_throws_validation_exception_on_400_for_invalid_money_format(): void
    {
        $priceTypeId = $this->createTestUuid();
        $offersData = [
            'offers' => [
                [
                    'product_id' => $this->createTestUuid(),
                    'prices' => [['price_type_id' => $priceTypeId, 'price' => ['amount' => '100.123', 'currency' => 'RUB']]], // Invalid money format
                ],
            ],
        ];
        $errorResponse = [
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => ['offers.0.prices.0.price.amount must match pattern ^-?\d+(\.\d{1,2})?$'],
            ],
        ];

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/offers', $offersData, null) // Corrected: Explicitly match offersData
            ->andThrow(new ValidationException(
                $errorResponse['error']['message'],
                $errorResponse['error']['details'],
                400
            ));

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(400);

        $this->mockConnector->post('/offers', $offersData);
    }

    /** @test */
    public function get_price_types_returns_list(): void
    {
        $mockPriceTypesData = [
            'price_types' => [
                ['id' => $this->createTestUuid(), 'name' => 'Retail Price', 'currency' => 'RUB', 'is_default' => true],
                ['id' => $this->createTestUuid(), 'name' => 'Wholesale Price', 'currency' => 'RUB', 'is_default' => false],
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/offers/price-types', []) // Corrected: Explicitly match empty array
            ->andReturn(new Response(200, [], json_encode($mockPriceTypesData)));

        $response = $this->mockConnector->get('/offers/price-types');
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $responseData['price_types']);
        $this->assertEquals('Retail Price', $responseData['price_types'][0]['name']);
        $this->assertIsBool($responseData['price_types'][0]['is_default']);
    }
}
