<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Services;

use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Http\Client\Dto\Response\ResponseDto;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Services\CounterpartyService;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Mockery;

class CounterpartyServiceTest extends TestCase
{
    protected CounterpartyService $counterpartyService;

    protected Mockery\MockInterface|HttpClientInterface $mockHttp;

    protected Mockery\MockInterface|CommandBusInterface $mockCommandBus;

    protected Mockery\MockInterface|QueryBusInterface $mockQueryBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockHttp = Mockery::mock(HttpClientInterface::class);
        $this->mockCommandBus = Mockery::mock(CommandBusInterface::class);
        $this->mockQueryBus = Mockery::mock(QueryBusInterface::class);
        $this->counterpartyService = new CounterpartyService($this->mockHttp, $this->mockCommandBus, $this->mockQueryBus);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function get_counterparties_returns_list(): void
    {
        $data = $this->createCounterpartyData();
        $mockResponse = ['counterparties' => [['id' => $data->id, 'name' => $data->name, 'type' => $data->type]], 'pagination' => ['page' => 1, 'limit' => 100, 'total' => 1, 'has_next' => false]];
        $responseDto = new ResponseDto(200, [], $mockResponse, new Psr7Response(200, [], json_encode($mockResponse)));
        $this->mockHttp->shouldReceive('get')->once()->with('/counterparties', ['page' => 1, 'limit' => 100, 'include_deleted' => 'false'])->andReturn($responseDto);
        $list = $this->counterpartyService->getCounterparties(page: 1, limit: 100);
        $this->assertCount(1, $list->counterparties);
    }
}
