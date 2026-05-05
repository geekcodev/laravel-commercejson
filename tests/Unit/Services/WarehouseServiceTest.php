<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Services;

use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Http\Client\Dto\Response\ResponseDto;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Services\WarehouseService;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Mockery;

class WarehouseServiceTest extends TestCase
{
    protected WarehouseService $warehouseService;

    protected Mockery\MockInterface|HttpClientInterface $mockHttp;

    protected Mockery\MockInterface|CommandBusInterface $mockCommandBus;

    protected Mockery\MockInterface|QueryBusInterface $mockQueryBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockHttp = Mockery::mock(HttpClientInterface::class);
        $this->mockCommandBus = Mockery::mock(CommandBusInterface::class);
        $this->mockQueryBus = Mockery::mock(QueryBusInterface::class);
        $this->warehouseService = new WarehouseService($this->mockHttp, $this->mockCommandBus, $this->mockQueryBus);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function get_warehouses_returns_list(): void
    {
        $mockResponse = ['warehouses' => [['id' => $this->createTestUuid(), 'name' => 'WH1']]];
        $responseDto = new ResponseDto(200, [], $mockResponse, new Psr7Response(200, [], json_encode($mockResponse)));
        $this->mockHttp->shouldReceive('get')->once()->with('/warehouses', [])->andReturn($responseDto);
        $warehouses = $this->warehouseService->getWarehouses();
        $this->assertCount(1, $warehouses);
    }
}
