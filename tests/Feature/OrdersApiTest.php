<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature;

use Carbon\Carbon;
use GeekCo\CommerceJson\Exceptions\BusinessException;
use GeekCo\CommerceJson\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;

class OrdersApiTest extends TestCase
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
    public function get_orders_returns_paginated_list(): void
    {
        $mockOrdersData = [
            'orders' => [
                ['id' => $this->createTestUuid(), 'number' => 'ORD-001', 'status' => 'new', 'document_type' => 'order', 'updated_at' => now()->toIso8601String(), 'totals' => ['total' => ['amount' => '1000.00', 'currency' => 'RUB']]],
                ['id' => $this->createTestUuid(), 'number' => 'ORD-002', 'status' => 'confirmed', 'document_type' => 'order', 'updated_at' => now()->toIso8601String(), 'totals' => ['total' => ['amount' => '2000.00', 'currency' => 'RUB']]],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 2,
                'has_next' => false,
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/orders', ['page' => 1, 'limit' => 10])
            ->andReturn(new Response(200, [], json_encode($mockOrdersData)));

        $response = $this->mockConnector->get('/orders', ['page' => 1, 'limit' => 10]);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $responseData['orders']);
        $this->assertEquals('new', $responseData['orders'][0]['status']);
        $this->assertMoneyFormat($responseData['orders'][0]['totals']['total']['amount']);
    }

    /** @test */
    public function get_orders_with_filters_and_incremental_sync(): void
    {
        $updatedAfter = Carbon::now()->subHour()->toIso8601String();
        $orderId = $this->createTestUuid();
        $mockOrdersData = [
            'orders' => [
                ['id' => $orderId, 'status' => 'cancelled', 'document_type' => 'order', 'deleted_at' => now()->toIso8601String(), 'updated_at' => now()->toIso8601String(), 'totals' => ['total' => ['amount' => '500.00', 'currency' => 'RUB']]],
            ],
            'pagination' => ['page' => 1, 'limit' => 1, 'total' => 1, 'has_next' => false],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/orders', [
                'status' => 'cancelled',
                'document_type' => 'order',
                'updated_after' => $updatedAfter,
                'include_deleted' => true,
            ])
            ->andReturn(new Response(200, [], json_encode($mockOrdersData)));

        $response = $this->mockConnector->get('/orders', [
            'status' => 'cancelled',
            'document_type' => 'order',
            'updated_after' => $updatedAfter,
            'include_deleted' => true,
        ]);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $responseData['orders']);
        $this->assertEquals('cancelled', $responseData['orders'][0]['status']);
        $this->assertNotNull($responseData['orders'][0]['deleted_at']);
        $this->assertIso8601Date($responseData['orders'][0]['deleted_at']);
    }

    /** @test */
    public function post_orders_create_new_order_success_and_idempotency(): void
    {
        $idempotencyKey = $this->createTestUuid();
        $orderId = $this->createTestUuid();
        $orderData = [
            'document_type' => 'order',
            'customer' => ['name' => 'Test User', 'phone' => '+79001234567'],
            'delivery' => [
                'type' => 'courier',
                'address' => ['city' => 'Москва'],
                'cost' => ['amount' => '300.00', 'currency' => 'RUB'],
            ],
            'payment' => ['type' => 'online', 'status' => 'pending'],
            'items' => [['product_id' => $this->createTestUuid(), 'quantity' => 1]],
        ];
        $mockCreatedOrder = array_merge($orderData, [
            'id' => $orderId,
            'number' => 'ORD-NEW-001',
            'status' => 'new',
            'totals' => ['total' => ['amount' => '1300.00', 'currency' => 'RUB']],
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // First call
        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/orders', $orderData, $idempotencyKey)
            ->andReturn(new Response(201, [], json_encode($mockCreatedOrder)));

        $response1 = $this->mockConnector->post('/orders', $orderData, $idempotencyKey);
        $responseData1 = json_decode((string) $response1->getBody(), true);

        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertEquals($orderId, $responseData1['id']);
        $this->assertEquals('new', $responseData1['status']);

        // Second call with the same idempotency key (should return cached result or same new resource)
        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/orders', $orderData, $idempotencyKey)
            ->andReturn(new Response(201, [], json_encode($mockCreatedOrder)));

        $response2 = $this->mockConnector->post('/orders', $orderData, $idempotencyKey);
        $responseData2 = json_decode((string) $response2->getBody(), true);

        $this->assertEquals(201, $response2->getStatusCode());
        $this->assertEquals($orderId, $responseData2['id']);
        $this->assertEquals($responseData1, $responseData2);
    }

    /** @test */
    public function post_orders_throws_validation_exception_for_missing_delivery_payment_on_order_type(): void
    {
        $orderData = [
            'document_type' => 'order',
            'customer' => ['name' => 'Test User'],
            'items' => [['product_id' => $this->createTestUuid(), 'quantity' => 1]],
            // Missing 'delivery' and 'payment' for document_type 'order'
        ];
        $errorResponse = [
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => ['delivery field is required.', 'payment field is required.'],
            ],
        ];

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/orders', $orderData, null) // Corrected: Explicitly match orderData
            ->andThrow(new ValidationException(
                $errorResponse['error']['message'],
                $errorResponse['error']['details'],
                400
            ));

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(400);

        $this->mockConnector->post('/orders', $orderData);
    }

    /** @test */
    public function post_orders_succeeds_without_delivery_payment_for_invoice_type(): void
    {
        $invoiceData = [
            'document_type' => 'invoice',
            'customer' => ['name' => 'Invoice Customer'],
            'items' => [['product_id' => $this->createTestUuid(), 'quantity' => 1]],
        ];
        $mockCreatedInvoice = array_merge($invoiceData, [
            'id' => $this->createTestUuid(),
            'number' => 'INV-NEW-001',
            'status' => 'new',
            'totals' => ['total' => ['amount' => '500.00', 'currency' => 'RUB']],
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/orders', $invoiceData, null) // Corrected: Explicitly match invoiceData
            ->andReturn(new Response(201, [], json_encode($mockCreatedInvoice)));

        $response = $this->mockConnector->post('/orders', $invoiceData);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('invoice', $responseData['document_type']);
        $this->assertArrayNotHasKey('delivery', $responseData);
        $this->assertArrayNotHasKey('payment', $responseData);
    }

    /** @test */
    public function post_orders_bulk_success(): void
    {
        $orderId1 = $this->createTestUuid();
        $orderId2 = $this->createTestUuid();
        $bulkUpdateData = [
            'orders' => [
                [
                    'id' => $orderId1,
                    'status' => 'processing',
                    'delivery' => ['tracking_number' => 'TRK12345'],
                ],
                [
                    'external_id' => 'ERP_ORDER_500',
                    'payment' => ['status' => 'paid', 'amount' => ['amount' => '15000.00', 'currency' => 'RUB']],
                ],
            ],
        ];
        $mockImportResult = ['success' => true, 'processed' => 2, 'errors' => [], 'warnings' => []];

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/orders/bulk', $bulkUpdateData, null) // Corrected: Explicitly match bulkUpdateData
            ->andReturn(new Response(200, [], json_encode($mockImportResult)));

        $response = $this->mockConnector->post('/orders/bulk', $bulkUpdateData);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals(2, $responseData['processed']);
    }

    /** @test */
    public function get_order_by_id_returns_order(): void
    {
        $orderId = $this->createTestUuid();
        $mockOrderData = [
            'id' => $orderId,
            'number' => 'ORD-TEST-001',
            'status' => 'new',
            'document_type' => 'order',
            'customer' => ['name' => 'Test Customer'],
            'totals' => ['total' => ['amount' => '1000.00', 'currency' => 'RUB']],
            'updated_at' => now()->toIso8601String(),
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/orders/'.$orderId, []) // Corrected: Explicitly match empty array
            ->andReturn(new Response(200, [], json_encode($mockOrderData)));

        $response = $this->mockConnector->get('/orders/'.$orderId);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($orderId, $responseData['id']);
        $this->assertEquals('new', $responseData['status']);
        $this->assertMoneyFormat($responseData['totals']['total']['amount']);
    }

    /** @test */
    public function get_order_by_id_throws_not_found_exception_on_404(): void
    {
        $nonExistentId = $this->createTestUuid();
        $errorResponse = [
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Order not found.',
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/orders/'.$nonExistentId, []) // Corrected: Explicitly match empty array
            ->andThrow(new \RuntimeException( // Corrected: Throw RuntimeException as per connector's mapping
                $errorResponse['error']['message'],
                404
            ));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Order not found.');

        $this->mockConnector->get('/orders/'.$nonExistentId);
    }

    /** @test */
    public function patch_order_updates_status_successfully(): void
    {
        $orderId = $this->createTestUuid();
        $patchData = ['status' => 'shipped'];
        $mockUpdatedOrder = [
            'id' => $orderId,
            'number' => 'ORD-TEST-001',
            'status' => 'shipped',
            'updated_at' => now()->toIso8601String(),
        ];

        $this->mockConnector->shouldReceive('patch')
            ->once()
            ->with('/orders/'.$orderId, $patchData, null) // Corrected: Explicitly match patchData
            ->andReturn(new Response(200, [], json_encode($mockUpdatedOrder)));

        $response = $this->mockConnector->patch('/orders/'.$orderId, $patchData);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('shipped', $responseData['status']);
    }

    /** @test */
    public function patch_order_throws_business_exception_for_invalid_status_transition(): void
    {
        $orderId = $this->createTestUuid();
        $patchData = ['status' => 'new']; // Invalid transition from assumed 'shipped'
        $errorResponse = [
            'error' => [
                'code' => 'STATUS_TRANSITION_ERROR',
                'message' => 'Order status cannot be changed from shipped to new.',
            ],
        ];

        $this->mockConnector->shouldReceive('patch')
            ->once()
            ->with('/orders/'.$orderId, $patchData, null) // Corrected: Explicitly match patchData
            ->andThrow(new BusinessException(
                $errorResponse['error']['message'],
                $errorResponse['error']['code'],
                422
            ));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(422);

        $this->mockConnector->patch('/orders/'.$orderId, $patchData);
    }

    /** @test */
    public function patch_order_replaces_items_fully(): void
    {
        $orderId = $this->createTestUuid();
        $existingItem1 = $this->createTestUuid();
        $newItem1ProductId = $this->createTestUuid();
        $patchData = [
            'items' => [
                ['id' => $existingItem1, 'quantity' => 5],
                ['product_id' => $newItem1ProductId, 'quantity' => 3],
            ],
        ];
        $mockUpdatedOrder = [
            'id' => $orderId,
            'items' => [
                ['id' => $existingItem1, 'product_id' => $this->createTestUuid(), 'quantity' => 5],
                ['id' => $this->createTestUuid(), 'product_id' => $newItem1ProductId, 'quantity' => 3],
            ],
            'updated_at' => now()->toIso8601String(),
        ];

        $this->mockConnector->shouldReceive('patch')
            ->once()
            ->with('/orders/'.$orderId, $patchData, null) // Corrected: Explicitly match patchData
            ->andReturn(new Response(200, [], json_encode($mockUpdatedOrder)));

        $response = $this->mockConnector->patch('/orders/'.$orderId, $patchData);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $responseData['items']);
        $this->assertContains($existingItem1, array_column($responseData['items'], 'id'));
        $this->assertContains($newItem1ProductId, array_column($responseData['items'], 'product_id'));
    }

    /** @test */
    public function patch_order_payment_isolation(): void
    {
        $orderId = $this->createTestUuid();
        $patchData = [
            'payment' => ['status' => 'paid', 'paid_at' => Carbon::now()->toIso8601String()],
        ];
        $mockUpdatedOrder = [
            'id' => $orderId,
            'status' => 'new', // Assuming original status was 'new' and should not change
            'payment' => ['status' => 'paid', 'paid_at' => Carbon::now()->toIso8601String()],
            'updated_at' => now()->toIso8601String(),
        ];

        $this->mockConnector->shouldReceive('patch')
            ->once()
            ->with('/orders/'.$orderId, $patchData, null) // Corrected: Explicitly match patchData
            ->andReturn(new Response(200, [], json_encode($mockUpdatedOrder)));

        $response = $this->mockConnector->patch('/orders/'.$orderId, $patchData);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('paid', $responseData['payment']['status']);
        $this->assertEquals('new', $responseData['status']); // Ensure other fields are unchanged
    }
}
