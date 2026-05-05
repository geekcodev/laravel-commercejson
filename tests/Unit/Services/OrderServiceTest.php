<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Services;

use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Http\Client\Dto\Response\ResponseDto;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Services\OrderService;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Mockery;

class OrderServiceTest extends TestCase
{
    protected OrderService $orderService;

    protected Mockery\MockInterface|HttpClientInterface $mockHttp;

    protected Mockery\MockInterface|CommandBusInterface $mockCommandBus;

    protected Mockery\MockInterface|QueryBusInterface $mockQueryBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockHttp = Mockery::mock(HttpClientInterface::class);
        $this->mockCommandBus = Mockery::mock(CommandBusInterface::class);
        $this->mockQueryBus = Mockery::mock(QueryBusInterface::class);
        $this->orderService = new OrderService($this->mockHttp, $this->mockCommandBus, $this->mockQueryBus);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function get_orders_returns_order_list(): void
    {
        $orderData = $this->createOrderData();
        $mockResponse = ['orders' => [['id' => $orderData->id, 'number' => $orderData->number, 'status' => $orderData->status]], 'pagination' => ['page' => 1, 'limit' => 100, 'total' => 1, 'has_next' => false]];
        $responseDto = new ResponseDto(200, [], $mockResponse, new Psr7Response(200, [], json_encode($mockResponse)));
        $this->mockHttp->shouldReceive('get')->once()->with('/orders', ['page' => 1, 'limit' => 100])->andReturn($responseDto);
        $orderList = $this->orderService->getOrders(page: 1, limit: 100);
        $this->assertCount(1, $orderList->orders);
    }

    /** @test */
    public function get_order_by_id_returns_order(): void
    {
        $orderData = $this->createOrderData();
        $mockResponse = ['id' => $orderData->id, 'number' => $orderData->number, 'status' => $orderData->status];
        $responseDto = new ResponseDto(200, [], $mockResponse, new Psr7Response(200, [], json_encode($mockResponse)));
        $this->mockHttp->shouldReceive('get')->once()->with("/orders/{$orderData->id}", [])->andReturn($responseDto);
        $order = $this->orderService->getOrder($orderData->id);
        $this->assertEquals($orderData->id, $order->id);
    }

    /** @test */
    public function update_order_status_updates_order(): void
    {
        $orderId = $this->createTestUuid();
        $mockResponse = ['id' => $orderId, 'status' => 'shipped'];
        $responseDto = new ResponseDto(200, [], $mockResponse, new Psr7Response(200, [], json_encode($mockResponse)));
        $this->mockHttp->shouldReceive('patch')->once()->with("/orders/{$orderId}", ['status' => 'shipped'], null)->andReturn($responseDto);
        $order = $this->orderService->updateOrderStatus($orderId, 'shipped');
        $this->assertEquals('shipped', $order->status);
    }
}
