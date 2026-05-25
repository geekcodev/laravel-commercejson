<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Services;

use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Http\Client\Dto\Response\ResponseDto;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Models\Offer;
use GeekCo\CommerceJson\Services\OfferService;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Mockery;

class OfferServiceTest extends TestCase
{
    protected OfferService $offerService;

    protected Mockery\MockInterface|HttpClientInterface $mockHttp;

    protected Mockery\MockInterface|CommandBusInterface $mockCommandBus;

    protected Mockery\MockInterface|QueryBusInterface $mockQueryBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockHttp = Mockery::mock(HttpClientInterface::class);
        $this->mockCommandBus = Mockery::mock(CommandBusInterface::class);
        $this->mockQueryBus = Mockery::mock(QueryBusInterface::class);
        $this->offerService = new OfferService($this->mockHttp, $this->mockCommandBus, $this->mockQueryBus);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function get_offers_returns_offer_list(): void
    {
        $offerData = $this->createOfferData();
        $mockResponse = ['offers' => [['id' => $offerData->id, 'product_id' => $offerData->productId, 'price' => $offerData->price]], 'pagination' => ['page' => 1, 'limit' => 100, 'total' => 1, 'has_next' => false]];
        $responseDto = new ResponseDto(200, [], $mockResponse, new Psr7Response(200, [], json_encode($mockResponse)));
        $this->mockHttp->shouldReceive('get')->once()->with('/offers', ['page' => 1, 'limit' => 100])->andReturn($responseDto);
        $offerList = $this->offerService->getOffers(page: 1, limit: 100);
        $this->assertCount(1, $offerList->offers);
    }

    /** @test */
    public function sync_offer_dispatches_command(): void
    {
        $offerData = $this->createOfferData();
        $mockOffer = Mockery::mock(Offer::class)->makePartial();
        $mockOffer->shouldIgnoreMissing();
        $mockOffer->id = $offerData->id;
        $mockOffer->wasRecentlyCreated = true;
        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn($mockOffer);
        $offer = $this->offerService->syncOffer($offerData);
        $this->assertInstanceOf(Offer::class, $offer);
        $this->assertEquals($offerData->id, $offer->id);
    }
}
