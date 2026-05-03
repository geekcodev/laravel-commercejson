<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Services;

use GeekCo\CommerceJson\Data\OfferData;
use GeekCo\CommerceJson\Data\OfferImportData;
use GeekCo\CommerceJson\Data\OfferListData;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Models\Offer;
use GeekCo\CommerceJson\Services\OfferService;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Mockery;

/**
 * Тесты для OfferService
 *
 * @covers \GeekCo\CommerceJson\Services\OfferService
 */
class OfferServiceTest extends TestCase
{
    protected OfferService $offerService;

    protected \Mockery\MockInterface|CommerceJsonConnector $mockConnector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConnector = Mockery::mock(CommerceJsonConnector::class);
        $this->offerService = new OfferService($this->mockConnector);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper method to create a mock response with proper body stream
     */
    protected static function createMockResponse(array $content): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($content));
    }

    /** @test */
    public function get_offers_returns_offer_list_data(): void
    {
        $offerId1 = $this->createTestUuid();
        $offerId2 = $this->createTestUuid();
        $priceTypeId = $this->createTestUuid();
        $mockResponseContent = [
            'valid_from' => null,
            'valid_to' => null,
            'price_types' => [],
            'warehouses' => [],
            'offers' => [
                [
                    'product_id' => $offerId1,
                    'variant_id' => null,
                    'prices' => [
                        [
                            'price_type_id' => $priceTypeId,
                            'price' => ['amount' => '100.00', 'currency' => 'RUB'],
                            'min_quantity' => 1.000,
                            'unit' => ['code' => '796', 'short_name' => 'шт', 'full_name' => 'штука', 'international' => 'PCE'],
                        ],
                    ],
                    'stocks' => null,
                ],
                [
                    'product_id' => $offerId2,
                    'variant_id' => null,
                    'prices' => [
                        [
                            'price_type_id' => $priceTypeId,
                            'price' => ['amount' => '200.00', 'currency' => 'RUB'],
                            'min_quantity' => 1.000,
                            'unit' => ['code' => '796', 'short_name' => 'шт', 'full_name' => 'штука', 'international' => 'PCE'],
                        ],
                    ],
                    'stocks' => null,
                ],
            ],
            'pagination' => ['page' => 1, 'limit' => 50, 'total' => 2, 'has_next' => false],
        ];

        // Используем мок-коннектор для перехвата вызова GET
        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/offers', Mockery::any())
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($mockResponseContent)));

        $offerListData = $this->offerService->getOffers(page: 1, limit: 50);

        $this->assertInstanceOf(OfferListData::class, $offerListData);
        $this->assertCount(2, $offerListData->offers);
        $this->assertEquals($offerId1, $offerListData->offers[0]->productId);
        $this->assertEquals(2, $offerListData->pagination->total);
    }

    /** @test */
    public function get_offers_filters_are_passed_correctly(): void
    {
        $priceTypeId = $this->createTestUuid();
        $warehouseId = $this->createTestUuid();
        $updatedAfter = now()->subDays(2);
        $mockResponseContent = ['offers' => [], 'pagination' => ['page' => 1, 'limit' => 50, 'total' => 0, 'has_next' => false]];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/offers', [
                'page' => 1,
                'limit' => 50,
                'price_type_id' => $priceTypeId,
                'warehouse_id' => $warehouseId,
                'updated_after' => $updatedAfter->format(\DateTime::ATOM),
            ])
            ->andReturnUsing(function () use ($mockResponseContent) {
                return self::createMockResponse($mockResponseContent);
            });

        $this->offerService->getOffers(
            page: 1,
            limit: 50,
            priceTypeId: $priceTypeId,
            warehouseId: $warehouseId,
            updatedAfter: $updatedAfter
        );
    }

    /** @test */
    public function import_offers_sends_correct_data_and_idempotency_key(): void
    {
        $idempotencyKey = $this->createTestUuid();
        $offerImportData = OfferImportData::from([
            'offers' => [
                [
                    'product_id' => $this->createTestUuid(),
                    'variant_id' => null,
                    'prices' => [
                        [
                            'price_type_id' => $this->createTestUuid(),
                            'price' => ['amount' => '150.00', 'currency' => 'RUB'],
                            'min_quantity' => 1.000,
                            'unit' => ['code' => '796', 'short_name' => 'шт', 'full_name' => 'штука', 'international' => 'PCE'],
                        ],
                    ],
                    'stocks' => null,
                ],
            ],
        ]);
        $mockResponseContent = ['success' => true, 'processed' => 1, 'errors' => [], 'warnings' => []];

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/offers', $offerImportData->toArray(), $idempotencyKey)
            ->andReturnUsing(function () use ($mockResponseContent) {
                return self::createMockResponse($mockResponseContent);
            });

        $importResult = $this->offerService->importOffers($offerImportData, $idempotencyKey);

        $this->assertTrue($importResult->success);
        $this->assertEquals(1, $importResult->processed);
    }

    /** @test */
    public function get_price_types_returns_array(): void
    {
        $mockResponseContent = [
            'price_types' => [
                ['id' => $this->createTestUuid(), 'name' => 'Retail', 'currency' => 'RUB', 'is_default' => true],
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/offers/price-types', [])
            ->andReturnUsing(function () use ($mockResponseContent) {
                return self::createMockResponse($mockResponseContent);
            });

        $priceTypes = $this->offerService->getPriceTypes();

        $this->assertIsArray($priceTypes);
        $this->assertCount(1, $priceTypes);
        $this->assertEquals('Retail', $priceTypes[0]['name']);
    }

    /** @test */
    public function get_warehouses_returns_array(): void
    {
        $mockResponseContent = [
            'warehouses' => [
                ['id' => $this->createTestUuid(), 'name' => 'Main WH', 'code' => 'MAIN', 'is_default' => true],
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/warehouses', [])
            ->andReturnUsing(function () use ($mockResponseContent) {
                return self::createMockResponse($mockResponseContent);
            });

        $warehouses = $this->offerService->getWarehouses();

        $this->assertIsArray($warehouses);
        $this->assertCount(1, $warehouses);
        $this->assertEquals('Main WH', $warehouses[0]['name']);
    }

    /** @test */
    public function get_product_offers_retrieves_all_offers_for_product_across_pages(): void
    {
        $productId = $this->createTestUuid();
        $offerId1 = $this->createTestUuid();
        $offerId2 = $this->createTestUuid();
        $otherProductId = $this->createTestUuid();
        $priceTypeId = $this->createTestUuid();

        // Page 1: 1 offer for target product, 1 for another product, has_next = true
        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/offers', ['page' => 1, 'limit' => 100])
            ->andReturn(new Response(200, [], json_encode([
                'offers' => [
                    [
                        'product_id' => $productId,
                        'variant_id' => null,
                        'prices' => [
                            [
                                'price_type_id' => $priceTypeId,
                                'price' => ['amount' => '100.00', 'currency' => 'RUB'],
                                'min_quantity' => 1.000,
                                'unit' => ['code' => '796', 'short_name' => 'шт', 'full_name' => 'штука', 'international' => 'PCE'],
                            ],
                        ],
                        'stocks' => null,
                    ],
                    [
                        'product_id' => $otherProductId,
                        'variant_id' => null,
                        'prices' => [
                            [
                                'price_type_id' => $priceTypeId,
                                'price' => ['amount' => '50.00', 'currency' => 'RUB'],
                                'min_quantity' => 1.000,
                                'unit' => ['code' => '796', 'short_name' => 'шт', 'full_name' => 'штука', 'international' => 'PCE'],
                            ],
                        ],
                        'stocks' => null,
                    ],
                ],
                'pagination' => ['page' => 1, 'limit' => 100, 'total' => 3, 'has_next' => true],
            ])));

        // Page 2: 1 offer for target product, has_next = false
        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/offers', ['page' => 2, 'limit' => 100])
            ->andReturn(new Response(200, [], json_encode([
                'offers' => [
                    [
                        'product_id' => $productId,
                        'variant_id' => null,
                        'prices' => [
                            [
                                'price_type_id' => $priceTypeId,
                                'price' => ['amount' => '120.00', 'currency' => 'RUB'],
                                'min_quantity' => 1.000,
                                'unit' => ['code' => '796', 'short_name' => 'шт', 'full_name' => 'штука', 'international' => 'PCE'],
                            ],
                        ],
                        'stocks' => null,
                    ],
                ],
                'pagination' => ['page' => 2, 'limit' => 100, 'total' => 3, 'has_next' => false],
            ])));

        $productOffers = $this->offerService->getProductOffers($productId);

        $this->assertCount(2, $productOffers);
        $this->assertEquals($productId, $productOffers[0]->productId);
        $this->assertEquals($productId, $productOffers[1]->productId);
    }

    /** @test */
    public function sync_offer_creates_or_updates_offer_model(): void
    {
        $productId = $this->createTestUuid();
        $priceTypeId = $this->createTestUuid();

        $offerData = OfferData::from([
            'product_id' => $productId,
            'variant_id' => null,
            'prices' => [
                [
                    'price_type_id' => $priceTypeId,
                    'price' => ['amount' => '100.00', 'currency' => 'RUB'],
                    'min_quantity' => 1.000,
                    'unit' => ['code' => '796', 'short_name' => 'шт', 'full_name' => 'штука', 'international' => 'PCE'],
                ],
            ],
            'stocks' => null,
        ]);

        // First sync - should create
        $offer = $this->offerService->syncOffer($offerData);

        $this->assertInstanceOf(Offer::class, $offer);
        $this->assertEquals($productId, $offer->product_id);
        $this->assertTrue($offer->wasRecentlyCreated);

        // Second sync - should update
        $newOfferData = OfferData::from([
            'product_id' => $productId,
            'variant_id' => null,
            'prices' => [
                [
                    'price_type_id' => $priceTypeId,
                    'price' => ['amount' => '200.00', 'currency' => 'RUB'],
                    'min_quantity' => 1.000,
                    'unit' => ['code' => '796', 'short_name' => 'шт', 'full_name' => 'штука', 'international' => 'PCE'],
                ],
            ],
            'stocks' => null,
        ]);
        $updatedOffer = $this->offerService->syncOffer($newOfferData);

        $this->assertEquals($offer->product_id, $updatedOffer->product_id);
        $this->assertFalse($updatedOffer->wasRecentlyCreated);
    }
}
