<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Services;

use DateTimeInterface;
use GeekCo\CommerceJson\Data\AddressData;
use GeekCo\CommerceJson\Data\MoneyData;
use GeekCo\CommerceJson\Data\OrderCreateData;
use GeekCo\CommerceJson\Data\OrderCustomerData;
use GeekCo\CommerceJson\Data\OrderDeliveryData; // Added as use statement
use GeekCo\CommerceJson\Data\OrderImportData;
use GeekCo\CommerceJson\Data\OrderItemCreateData;
use GeekCo\CommerceJson\Data\OrderPaymentData; // Added
use GeekCo\CommerceJson\Enums\CurrencyEnum; // Added
use GeekCo\CommerceJson\Enums\DeliveryMethodEnum; // Added
use GeekCo\CommerceJson\Enums\PaymentMethodEnum;
use GeekCo\CommerceJson\Events\OrderCreated;
use GeekCo\CommerceJson\Events\OrderUpdated;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Services\OrderService;
use GeekCo\CommerceJson\Tests\TestCase; // Required for mock responses
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Mockery;

// Added for event faking

/**
 * Тесты для OrderService
 *
 * @covers \GeekCo\CommerceJson\Services\OrderService
 */
class OrderServiceTest extends TestCase
{
    protected OrderService $orderService;

    protected \Mockery\MockInterface|CommerceJsonConnector $mockConnector; // Use Mockery mock

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConnector = Mockery::mock(CommerceJsonConnector::class); // Initialize mock

        $this->orderService = new OrderService($this->mockConnector); // Inject mock
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function get_orders_returns_order_list(): void
    {
        $orderId1 = $this->createTestUuid();
        $orderId2 = $this->createTestUuid();
        $mockResponseContent = [
            'orders' => [
                [
                    'id' => $orderId1,
                    'number' => 'ORD-001',
                    'status' => 'new',
                    'document_type' => 'order',
                ],
                [
                    'id' => $orderId2,
                    'number' => 'ORD-002',
                    'status' => 'confirmed',
                    'document_type' => 'order',
                ],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 50,
                'total' => 2,
                'has_next' => false,
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/orders', Mockery::any())
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        $orderList = $this->orderService->getOrders(page: 1, limit: 50);

        $this->assertCount(2, $orderList->orders);
        $this->assertEquals(2, $orderList->pagination->total);
    }

    /**
     * @test
     */
    public function get_orders_with_status_filter(): void
    {
        $mockResponseContent = [
            'orders' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 50,
                'total' => 0,
                'has_next' => false,
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/orders', ['page' => 1, 'limit' => 50, 'status' => 'new', 'document_type' => 'order', 'include_deleted' => 'false'])
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        $this->orderService->getOrders(
            page: 1,
            limit: 50,
            status: 'new',
            documentType: 'order'
        );
    }

    /**
     * @test
     */
    public function get_orders_with_updated_after_filter(): void
    {
        $mockResponseContent = [
            'orders' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 50,
                'total' => 0,
                'has_next' => false,
            ],
        ];

        $updatedAfter = now()->subDay();

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/orders', [
                'page' => 1,
                'limit' => 50,
                'updated_after' => $updatedAfter->format(DateTimeInterface::ATOM),
                'include_deleted' => 'false',
            ])
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        $this->orderService->getOrders(
            page: 1,
            limit: 50,
            updatedAfter: $updatedAfter,
            includeDeleted: false
        );
    }

    /**
     * @test
     */
    public function get_order_by_id_returns_order(): void
    {
        $orderId = $this->createTestUuid();
        $mockResponseContent = [
            'id' => $orderId,
            'number' => 'ORD-TEST-001',
            'status' => 'new',
            'document_type' => 'order',
            'customer' => [
                'name' => 'Test Customer',
                'phone' => '+79001234567',
                'email' => 'test@example.com',
            ],
            'totals' => [
                'subtotal' => ['amount' => '1000.00', 'currency' => 'RUB'],
                'total' => ['amount' => '1200.00', 'currency' => 'RUB'],
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with("/orders/{$orderId}", [])
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        $order = $this->orderService->getOrder($orderId);

        $this->assertEquals($orderId, $order->id);
        $this->assertEquals('ORD-TEST-001', $order->number);
        $this->assertEquals('new', $order->status->value);
        $this->assertEquals('Test Customer', $order->customer->name);
    }

    /**
     * @test
     */
    public function get_order_not_found_returns_404(): void
    {
        $nonExistentId = $this->createTestUuid();
        $errorResponseContent = [
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Order not found',
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with("/orders/{$nonExistentId}", [])
            ->andThrow(new \RuntimeException('Order not found', 404)); // Use connector's exception

        $this->expectException(\RuntimeException::class);

        $this->orderService->getOrder($nonExistentId);
    }

    /**
     * @test
     */
    public function create_order_returns_created_order(): void
    {
        $orderId = $this->createTestUuid();
        $idempotencyKey = $this->createTestUuid();
        $mockResponseContent = [
            'id' => $orderId,
            'number' => 'ORD-NEW-001',
            'status' => 'new',
            'document_type' => 'order',
            'created_at' => now()->toIso8601String(),
        ];

        $orderCreateData = new OrderCreateData(
            documentType: 'order',
            customer: new OrderCustomerData(
                name: 'Test Customer',
                phone: '+79001234567',
                email: 'test@example.com',
            ),
            delivery: new OrderDeliveryData(
                type: DeliveryMethodEnum::Courier,
                address: new AddressData(
                    country: 'RU',
                    city: 'Москва',
                    street: 'Ленина',
                    house: '1',
                ),
                cost: new MoneyData(
                    amount: '300.00',
                    currency: CurrencyEnum::RUB,
                ),
            ),
            payment: new OrderPaymentData(
                type: PaymentMethodEnum::Card,
            ),
            items: [
                new OrderItemCreateData(
                    productId: $this->createTestUuid(),
                    variantId: null,
                    quantity: 2,
                ),
            ],
        );

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/orders', Mockery::any(), $idempotencyKey) // Use Mockery::any() for the data array
            ->andReturn(new Response(201, [], json_encode($mockResponseContent)));

        $order = $this->orderService->createOrder($orderCreateData, $idempotencyKey);

        $this->assertEquals($orderId, $order->id);
        $this->assertEquals('new', $order->status->value);
    }

    /**
     * @test
     */
    public function update_order_status_returns_updated_order(): void
    {
        $orderId = $this->createTestUuid();
        $mockResponseContent = [
            'id' => $orderId,
            'status' => 'confirmed',
            'updated_at' => now()->toIso8601String(),
        ];

        $this->mockConnector->shouldReceive('patch')
            ->once()
            ->with("/orders/{$orderId}", ['status' => 'confirmed'], null)
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        $order = $this->orderService->updateOrderStatus($orderId, 'confirmed');

        $this->assertEquals('confirmed', $order->status->value);
    }

    /**
     * @test
     */
    public function update_order_with_data(): void
    {
        $orderId = $this->createTestUuid();
        $updateData = ['comment' => 'Updated comment'];
        $mockResponseContent = [
            'id' => $orderId,
            'comment' => 'Updated comment',
            'updated_at' => now()->toIso8601String(),
        ];

        $this->mockConnector->shouldReceive('patch')
            ->once()
            ->with("/orders/{$orderId}", $updateData, null)
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        $order = $this->orderService->updateOrder($orderId, $updateData);

        $this->assertEquals('Updated comment', $order->comment);
    }

    /**
     * @test
     */
    public function import_orders_bulk(): void
    {
        $mockResponseContent = [
            'success' => true,
            'processed' => 2,
            'errors' => [],
        ];

        $orderImportData = new OrderImportData( // Corrected instantiation
            orders: [
                [
                    'id' => $this->createTestUuid(),
                    'status' => 'new',
                ],
                [
                    'id' => $this->createTestUuid(),
                    'status' => 'confirmed',
                ],
            ]
        );

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/orders/bulk', Mockery::any(), null) // Use Mockery::any() for the data array
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        $result = $this->orderService->importOrders($orderImportData);

        $this->assertTrue($result->success);
        $this->assertEquals(2, $result->processed);
    }

    /**
     * @test
     */
    public function get_new_orders_for_export(): void
    {
        $orderId1 = $this->createTestUuid();
        $orderId2 = $this->createTestUuid();
        $mockResponseContent = [
            'orders' => [
                ['id' => $orderId1, 'status' => 'new', 'number' => 'ORD-001'],
                ['id' => $orderId2, 'status' => 'new', 'number' => 'ORD-002'],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 50,
                'total' => 2,
                'has_next' => false,
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/orders', ['page' => 1, 'limit' => 50, 'status' => 'new', 'include_deleted' => 'false'])
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        $orderList = $this->orderService->getNewOrdersForExport(limit: 50);

        $this->assertCount(2, $orderList->orders);
        $this->assertEquals('new', $orderList->orders[0]->status->value);
    }

    /**
     * @test
     */
    public function get_orders_for_incremental_export(): void
    {
        $since = now()->subHour();
        $orderId1 = $this->createTestUuid();
        $mockResponseContent = [
            'orders' => [
                ['id' => $orderId1, 'updated_at' => $since->toIso8601String()],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 100,
                'total' => 1,
                'has_next' => false,
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/orders', ['page' => 1, 'limit' => 100, 'updated_after' => $since->format(\DateTime::ATOM), 'include_deleted' => 'false'])
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        $orderList = $this->orderService->getOrdersForIncrementalExport($since, limit: 100);

        $this->assertCount(1, $orderList->orders);
    }

    /**
     * @test
     */
    public function create_order_dispatches_event(): void
    {
        $orderId = $this->createTestUuid();
        $mockResponseContent = [
            'id' => $orderId,
            'status' => 'new',
        ];

        $orderCreateData = new OrderCreateData(
            documentType: 'order',
            role: null,
            customer: new OrderCustomerData(
                name: 'Test',
                phone: '+79001234567',
                email: 'test@test.com',
            ),
            delivery: new OrderDeliveryData(
                type: DeliveryMethodEnum::Courier,
                address: new AddressData(
                    city: 'Москва',
                ),
            ),
            payment: new OrderPaymentData(
                type: PaymentMethodEnum::Card,
            ),
            items: [],
        );

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/orders', Mockery::any(), null) // Use Mockery::any() for the data array
            ->andReturn(new Response(201, [], json_encode($mockResponseContent)));

        Event::fake(); // Use Event::fake() for Unit tests

        $this->orderService->createOrder($orderCreateData);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @test
     */
    public function update_order_dispatches_event(): void
    {
        $orderId = $this->createTestUuid();
        $mockResponseContent = [
            'id' => $orderId,
            'status' => 'confirmed',
        ];

        $this->mockConnector->shouldReceive('patch')
            ->once()
            ->with("/orders/{$orderId}", ['status' => 'confirmed'], null)
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        Event::fake(); // Use Event::fake() for Unit tests

        $this->orderService->updateOrderStatus($orderId, 'confirmed');

        Event::assertDispatched(OrderUpdated::class);
    }
}
