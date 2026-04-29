<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Http;

use GeekCo\CommerceJson\Exceptions\AuthenticationException;
use GeekCo\CommerceJson\Exceptions\BusinessException;
use GeekCo\CommerceJson\Exceptions\RateLimitException;
use GeekCo\CommerceJson\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Tests\TestCase;
use Illuminate\Support\Facades\Http;

/**
 * Тесты для HTTP клиента CommerceJSON
 *
 * @covers \GeekCo\CommerceJson\Http\Client\CommerceJsonConnector
 */
class CommerceJsonConnectorTest extends TestCase
{
    protected CommerceJsonConnector $connector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = new CommerceJsonConnector(
            baseUrl: 'https://api.test.com/v1',
            authToken: 'test-token',
            timeout: 30,
            authType: 'bearer'
        );
    }

    /**
     * @test
     */
    public function connector_initializes_with_correct_base_url(): void
    {
        $this->assertEquals('https://api.test.com/v1', $this->connector->getBaseUrl());
    }

    /**
     * @test
     */
    public function connector_has_auth_token(): void
    {
        $this->connector->setAuthToken('new-token');
        $this->assertNotNull($this->connector);
    }

    /**
     * @test
     */
    public function handshake_request_returns_response(): void
    {
        $mockResponse = [
            'version' => '1.0.8',
            'supported_versions' => ['1.0.8'],
            'server_time' => now()->toIso8601String(),
            'capabilities' => [
                'catalog' => true,
                'offers' => true,
                'orders' => true,
                'counterparties' => true,
                'warehouses' => true,
                'delta_sync' => true,
                'idempotency' => true,
                'max_page_size' => 1000,
            ],
            'session_token' => 'test-session-token',
        ];

        Http::fake([
            '*/handshake' => Http::response($mockResponse, 200),
        ]);

        $response = $this->connector->handshake();

        $this->assertEquals('1.0.8', $response->version);
        $this->assertContains('1.0.8', $response->supportedVersions);
        $this->assertTrue($response->capabilities->catalog);
        $this->assertTrue($response->capabilities->orders);
        $this->assertEquals('test-session-token', $response->sessionToken);
    }

    /**
     * @test
     */
    public function handshake_without_auth_returns_401(): void
    {
        Http::fake([
            '*/handshake' => Http::response([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication failed',
                ],
            ], 401),
        ]);

        $unauthorizedConnector = new CommerceJsonConnector(
            baseUrl: 'https://api.test.com/v1',
            authToken: 'invalid-token',
        );

        $this->expectException(AuthenticationException::class);

        $unauthorizedConnector->handshake();
    }

    /**
     * @test
     */
    public function get_request_with_pagination(): void
    {
        $mockResponse = [
            'products' => [
                ['id' => $this->createTestUuid(), 'name' => 'Product 1'],
                ['id' => $this->createTestUuid(), 'name' => 'Product 2'],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 100,
                'total' => 2,
                'has_next' => false,
            ],
        ];

        Http::fake([
            '*/catalog/products' => Http::response($mockResponse, 200),
        ]);

        $response = $this->connector->get('/catalog/products', [
            'page' => 1,
            'limit' => 100,
        ]);

        $this->assertEquals(200, $response->status());
        $data = $response->json();
        $this->assertCount(2, $data['products']);
        $this->assertEquals(1, $data['pagination']['page']);
    }

    /**
     * @test
     */
    public function get_request_with_updated_after_filter(): void
    {
        $mockResponse = [
            'products' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 100,
                'total' => 0,
                'has_next' => false,
            ],
        ];

        Http::fake([
            '*/catalog/products*' => Http::response($mockResponse, 200),
        ]);

        $updatedAfter = now()->subHour()->toIso8601String();

        $response = $this->connector->get('/catalog/products', [
            'updated_after' => $updatedAfter,
        ]);

        $this->assertEquals(200, $response->status());

        // Проверяем что запрос был с правильными параметрами
        Http::assertSent(function ($request) use ($updatedAfter) {
            return $request->url() === 'https://api.test.com/v1/catalog/products'
                && $request['updated_after'] === $updatedAfter;
        });
    }

    /**
     * @test
     */
    public function post_request_with_idempotency_key(): void
    {
        $idempotencyKey = $this->createTestUuid();

        $mockResponse = [
            'success' => true,
            'processed' => 1,
            'errors' => [],
        ];

        Http::fake([
            '*/catalog/products' => Http::response($mockResponse, 200),
        ]);

        $productData = [
            'products' => [
                [
                    'id' => $this->createTestUuid(),
                    'name' => 'Test Product',
                    'category_id' => $this->createTestUuid(),
                ],
            ],
        ];

        $response = $this->connector->post('/catalog/products', $productData, $idempotencyKey);

        $this->assertEquals(200, $response->status());

        // Проверяем что заголовок X-Idempotency-Key был отправлен
        Http::assertSent(function ($request) use ($idempotencyKey) {
            return $request->header('X-Idempotency-Key')[0] === $idempotencyKey;
        });
    }

    /**
     * @test
     */
    public function post_request_without_idempotency_key(): void
    {
        $mockResponse = [
            'success' => true,
            'processed' => 1,
        ];

        Http::fake([
            '*/catalog/products' => Http::response($mockResponse, 200),
        ]);

        $response = $this->connector->post('/catalog/products', [
            'products' => [['id' => $this->createTestUuid(), 'name' => 'Test']],
        ]);

        $this->assertEquals(200, $response->status());

        // Проверяем что заголовок X-Idempotency-Key НЕ был отправлен
        Http::assertSent(function ($request) {
            return ! isset($request->header('X-Idempotency-Key')[0]);
        });
    }

    /**
     * @test
     */
    public function patch_request_updates_resource(): void
    {
        $orderId = $this->createTestUuid();
        $idempotencyKey = $this->createTestUuid();

        $mockResponse = [
            'id' => $orderId,
            'status' => 'confirmed',
            'updated_at' => now()->toIso8601String(),
        ];

        Http::fake([
            "*/orders/{$orderId}" => Http::response($mockResponse, 200),
        ]);

        $response = $this->connector->patch("/orders/{$orderId}", [
            'status' => 'confirmed',
        ], $idempotencyKey);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('confirmed', $response->json('status'));
    }

    /**
     * @test
     */
    public function delete_request_deactivates_product(): void
    {
        $productId = $this->createTestUuid();

        $mockResponse = [
            'id' => $productId,
            'is_active' => false,
            'deleted_at' => now()->toIso8601String(),
        ];

        Http::fake([
            "*/catalog/products/{$productId}" => Http::response($mockResponse, 200),
        ]);

        $response = $this->connector->delete("/catalog/products/{$productId}");

        $this->assertEquals(200, $response->status());
        $this->assertFalse($response->json('is_active'));
        $this->assertNotNull($response->json('deleted_at'));
    }

    /**
     * @test
     */
    public function get_request_returns_404_for_nonexistent_resource(): void
    {
        $nonExistentId = $this->createTestUuid();

        Http::fake([
            "*/catalog/products/{$nonExistentId}" => Http::response([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Product not found',
                ],
            ], 404),
        ]);

        $this->expectException(\RuntimeException::class);

        $this->connector->get("/catalog/products/{$nonExistentId}");
    }

    /**
     * @test
     */
    public function post_request_returns_400_for_validation_error(): void
    {
        Http::fake([
            '*/catalog/products' => Http::response([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                    'details' => [
                        'The product_id field is required.',
                        'The name field must be a string.',
                    ],
                ],
            ], 400),
        ]);

        $this->expectException(ValidationException::class);

        $this->connector->post('/catalog/products', ['invalid' => 'data']);
    }

    /**
     * @test
     */
    public function post_request_returns_422_for_business_error(): void
    {
        Http::fake([
            '*/orders' => Http::response([
                'error' => [
                    'code' => 'STATUS_TRANSITION_ERROR',
                    'message' => 'Order status cannot be changed from shipped to new',
                ],
            ], 422),
        ]);

        $this->expectException(BusinessException::class);

        $this->connector->post('/orders', ['status' => 'new']);
    }

    /**
     * @test
     */
    public function request_returns_429_for_rate_limit(): void
    {
        Http::fake([
            '*/catalog/products' => Http::response([
                'error' => [
                    'code' => 'RATE_LIMITED',
                    'message' => 'Too many requests',
                ],
            ], 429, ['Retry-After' => '60']),
        ]);

        $this->expectException(RateLimitException::class);

        $this->connector->get('/catalog/products');
    }

    /**
     * @test
     */
    public function connector_retries_on_5xx_errors(): void
    {
        $callCount = 0;

        Http::fake(function ($request) use (&$callCount) {
            $callCount++;

            if ($callCount < 3) {
                return Http::response(['error' => 'Internal Server Error'], 500);
            }

            return Http::response(['success' => true], 200);
        });

        $this->connector->setRetryAttempts(3);

        $response = $this->connector->get('/catalog/products');

        $this->assertEquals(200, $response->status());
        $this->assertEquals(3, $callCount); // Должно быть 3 попытки
    }

    /**
     * @test
     */
    public function connector_sets_correct_headers(): void
    {
        Http::fake([
            '*/handshake' => Http::response(['version' => '1.0.8'], 200),
        ]);

        $this->connector->handshake();

        Http::assertSent(function ($request) {
            $headers = $request->headers();

            return isset($headers['Accept'][0]) && $headers['Accept'][0] === 'application/json'
                && isset($headers['Content-Type'][0]) && $headers['Content-Type'][0] === 'application/json'
                && isset($headers['Authorization'][0]) && str_starts_with($headers['Authorization'][0], 'Bearer ')
                && isset($headers['X-Request-ID'][0]);
        });
    }

    /**
     * @test
     */
    public function connector_can_set_session_token(): void
    {
        $sessionToken = 'test-session-'.$this->createTestUuid();

        $this->connector->setSessionToken($sessionToken);

        $this->assertEquals($sessionToken, $this->connector->getSessionToken());
    }

    /**
     * @test
     */
    public function connector_can_set_retry_attempts(): void
    {
        $this->connector->setRetryAttempts(5);

        $this->assertEquals(5, (fn () => $this->retryAttempts)->call($this->connector));
    }
}
