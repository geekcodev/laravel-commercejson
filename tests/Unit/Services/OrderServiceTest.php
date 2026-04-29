<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Services;

use GeekCo\CommerceJson\Data\AddressData;
use GeekCo\CommerceJson\Data\MoneyData;
use GeekCo\CommerceJson\Data\OrderCreateData;
use GeekCo\CommerceJson\Data\OrderCustomerData;
use GeekCo\CommerceJson\Data\OrderDeliveryData;
use GeekCo\CommerceJson\Data\OrderImportData;
use GeekCo\CommerceJson\Data\OrderItemCreateData;
use GeekCo\CommerceJson\Data\OrderPaymentData;
use GeekCo\CommerceJson\Events\OrderCreated;
use GeekCo\CommerceJson\Events\OrderUpdated;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Services\OrderService;
use GeekCo\CommerceJson\Tests\TestCase;
use Illuminate\Support\Facades\Http;

/**
 * Тесты для OrderService
 *
 * @covers \GeekCo\CommerceJson\Services\OrderService
 */
class OrderServiceTest extends TestCase
{
    protected OrderService $orderService;

    protected CommerceJsonConnector $connector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = new CommerceJsonConnector(
            baseUrl: 'https://api.test.com/v1',
            authToken: 'test-token',
        );

        $this->orderService = new OrderService($this->connector);
    }

    /**
     * @test
     */
    public function get_orders_returns_order_list(): void
    {
        Http::fake([
            '*/orders*' => Http::response([
                'orders' => [
                    [
                        'id' => $this->createTestUuid(),
                        'number' => 'ORD-001',
                        'status' => 'new',
                        'document_type' => 'order',
                    ],
                    [
                        'id' => $this->createTestUuid(),
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
            ], 200),
        ]);

        $orderList = $this->orderService->getOrders(page: 1, limit: 50);

        $this->assertCount(2, $orderList->orders);
        $this->assertEquals(2, $orderList->pagination->total);
    }

    /**
     * @test
     */
    public function get_orders_with_status_filter(): void
    {
        Http::fake([
            '*/orders*' => Http::response([
                'orders' => [],
                'pagination' => [
                    'page' => 1,
                    'limit' => 50,
                    'total' => 0,
                    'has_next' => false,
                ],
            ], 200),
        ]);

        $this->orderService->getOrders(
            page: 1,
            limit: 50,
            status: 'new',
            documentType: 'order'
        );

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'orders')
                && $request['status'] === 'new'
                && $request['document_type'] === 'order';
        });
    }

    /**
     * @test
     */
    public function get_orders_with_updated_after_filter(): void
    {
        Http::fake([
            '*/orders*' => Http::response([
                'orders' => [],
                'pagination' => [
                    'page' => 1,
                    'limit' => 50,
                    'total' => 0,
                    'has_next' => false,
                ],
            ], 200),
        ]);

        $updatedAfter = now()->subDay()->toIso8601String();

        $this->orderService->getOrders(
            page: 1,
            limit: 50,
            updatedAfter: new \DateTime($updatedAfter),
            includeDeleted: false
        );

        Http::assertSent(function ($request) {
            return isset($request['updated_after'])
                && $request['include_deleted'] === 'false';
        });
    }

    /**
     * @test
     */
    public function get_order_by_id_returns_order(): void
    {
        $orderId = $this->createTestUuid();

        Http::fake([
            "*/orders/{$orderId}" => Http::response([
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
            ], 200),
        ]);

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

        Http::fake([
            "*/orders/{$nonExistentId}" => Http::response([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Order not found',
                ],
            ], 404),
        ]);

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

        Http::fake([
            '*/orders' => Http::response([
                'id' => $orderId,
                'number' => 'ORD-NEW-001',
                'status' => 'new',
                'document_type' => 'order',
                'created_at' => now()->toIso8601String(),
            ], 201),
        ]);

        $orderCreateData = new OrderCreateData(
            documentType: 'order',
            customer: new OrderCustomerData(
                name: 'Test Customer',
                phone: '+79001234567',
                email: 'test@example.com',
            ),
            delivery: new OrderDeliveryData(
                type: 'courier',
                address: new AddressData(
                    country: 'RU',
                    city: 'Москва',
                    street: 'Ленина',
                    house: '1',
                ),
                cost: new MoneyData(
                    amount: '300.00',
                    currency: 'RUB',
                ),
            ),
            payment: new OrderPaymentData(
                type: 'card',
            ),
            items: [
                new OrderItemCreateData(
                    productId: $this->createTestUuid(),
                    quantity: 2,
                ),
            ],
        );

        $order = $this->orderService->createOrder($orderCreateData, $idempotencyKey);

        $this->assertEquals($orderId, $order->id);
        $this->assertEquals('new', $order->status->value);

        Http::assertSent(function ($request) use ($idempotencyKey) {
            return $request->header('X-Idempotency-Key')[0] === $idempotencyKey;
        });
    }

    /**
     * @test
     */
    public function update_order_status_returns_updated_order(): void
    {
        $orderId = $this->createTestUuid();
        $idempotencyKey = $this->createTestUuid();

        Http::fake([
            "*/orders/{$orderId}" => Http::response([
                'id' => $orderId,
                'status' => 'confirmed',
                'updated_at' => now()->toIso8601String(),
            ], 200),
        ]);

        $order = $this->orderService->updateOrderStatus($orderId, 'confirmed');

        $this->assertEquals('confirmed', $order->status->value);
    }

    /**
     * @test
     */
    public function update_order_with_data(): void
    {
        $orderId = $this->createTestUuid();

        Http::fake([
            "*/orders/{$orderId}" => Http::response([
                'id' => $orderId,
                'comment' => 'Updated comment',
                'updated_at' => now()->toIso8601String(),
            ], 200),
        ]);

        $order = $this->orderService->updateOrder($orderId, [
            'comment' => 'Updated comment',
        ]);

        $this->assertEquals('Updated comment', $order->comment);
    }

    /**
     * @test
     */
    public function import_orders_bulk(): void
    {
        Http::fake([
            '*/orders/bulk' => Http::response([
                'success' => true,
                'processed' => 2,
                'errors' => [],
            ], 200),
        ]);

        $orderImportData = new OrderImportData(
            orders: [
                [
                    'id' => $this->createTestUuid(),
                    'status' => 'new',
                    'document_type' => 'order',
                ],
                [
                    'id' => $this->createTestUuid(),
                    'status' => 'confirmed',
                    'document_type' => 'order',
                ],
            ]
        );

        $result = $this->orderService->importOrders($orderImportData);

        $this->assertTrue($result->success);
        $this->assertEquals(2, $result->processed);
    }

    /**
     * @test
     */
    public function get_new_orders_for_export(): void
    {
        Http::fake([
            '*/orders*' => Http::response([
                'orders' => [
                    ['id' => $this->createTestUuid(), 'status' => 'new', 'number' => 'ORD-001'],
                    ['id' => $this->createTestUuid(), 'status' => 'new', 'number' => 'ORD-002'],
                ],
                'pagination' => [
                    'page' => 1,
                    'limit' => 50,
                    'total' => 2,
                    'has_next' => false,
                ],
            ], 200),
        ]);

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

        Http::fake([
            '*/orders*' => Http::response([
                'orders' => [
                    ['id' => $this->createTestUuid(), 'updated_at' => $since->toIso8601String()],
                ],
                'pagination' => [
                    'page' => 1,
                    'limit' => 100,
                    'total' => 1,
                    'has_next' => false,
                ],
            ], 200),
        ]);

        $orderList = $this->orderService->getOrdersForIncrementalExport($since, limit: 100);

        $this->assertCount(1, $orderList->orders);

        Http::assertSent(function ($request) use ($since) {
            return isset($request['updated_after'])
                && str_contains($request['updated_after'], $since->format('Y-m-d'));
        });
    }

    /**
     * @test
     */
    public function create_order_dispatches_event(): void
    {
        $orderId = $this->createTestUuid();

        Http::fake([
            '*/orders' => Http::response([
                'id' => $orderId,
                'status' => 'new',
            ], 201),
        ]);

        $orderCreateData = new OrderCreateData(
            documentType: 'order',
            customer: new OrderCustomerData(
                name: 'Test',
                phone: '+79001234567',
                email: 'test@test.com',
            ),
            delivery: new OrderDeliveryData(
                type: 'courier',
                address: new AddressData(
                    city: 'Москва',
                ),
            ),
            payment: new OrderPaymentData(
                type: 'card',
            ),
            items: [],
        );

        $this->expectsEvents(OrderCreated::class);

        $this->orderService->createOrder($orderCreateData);
    }

    /**
     * @test
     */
    public function update_order_dispatches_event(): void
    {
        $orderId = $this->createTestUuid();

        Http::fake([
            "*/orders/{$orderId}" => Http::response([
                'id' => $orderId,
                'status' => 'confirmed',
            ], 200),
        ]);

        $this->expectsEvents(OrderUpdated::class);

        $this->orderService->updateOrderStatus($orderId, 'confirmed');
    }
}
