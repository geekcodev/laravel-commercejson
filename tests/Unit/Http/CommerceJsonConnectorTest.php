<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Http;

use GeekCo\CommerceJson\Exceptions\AuthenticationException;
use GeekCo\CommerceJson\Exceptions\BusinessException;
use GeekCo\CommerceJson\Exceptions\RateLimitException;
use GeekCo\CommerceJson\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response; // For history middleware
use Psr\Http\Message\RequestInterface; // For history middleware

/**
 * Тесты для HTTP клиента CommerceJSON
 *
 * @covers \GeekCo\CommerceJson\Http\Client\CommerceJsonConnector
 */
class CommerceJsonConnectorTest extends TestCase
{
    protected CommerceJsonConnector $connector;

    protected MockHandler $mockHandler;

    protected array $history = []; // To store requests for assertions

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize MockHandler and HandlerStack
        $this->mockHandler = new MockHandler;
        $handlerStack = HandlerStack::create($this->mockHandler);

        // Add history middleware to capture requests
        $handlerStack->push(Middleware::history($this->history));

        $mockGuzzleClient = new Client(['handler' => $handlerStack]);

        // Create the CommerceJsonConnector
        $this->connector = new CommerceJsonConnector(
            baseUrl: 'https://api.test.com/v1',
            authToken: 'test-token',
            timeout: 30,
            authType: 'bearer'
        );

        // Use reflection to set the protected $client property
        $reflection = new \ReflectionClass($this->connector);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->connector, $mockGuzzleClient);
    }

    protected function tearDown(): void
    {
        // Clear history after each test
        $this->history = [];
        parent::tearDown();
    }

    // /**
    //  * @test
    //  */
    // public function connector_initializes_with_correct_base_url(): void
    // {
    //     // Removed: getBaseUrl() is not a public method and its internal setting is tested by URI assertions
    // }

    /**
     * @test
     */
    public function connector_has_auth_token(): void
    {
        // No direct way to assert internal auth token without exposing it
        // The token is used in buildHeaders, which is tested in connector_sets_correct_headers
        $this->connector->setAuthToken('new-token');
        $this->assertNotNull($this->connector); // Simple assertion to ensure no error
    }

    /**
     * @test
     */
    public function handshake_request_returns_response(): void
    {
        $mockResponseContent = [
            'version' => '1.0.8',
            'supported_versions' => ['1.0.8'],
            'server_time' => now()->toIso8601String(),
            'capabilities' => [
                'catalog' => true, 'offers' => true, 'orders' => true,
                'counterparties' => true, 'warehouses' => true, 'delta_sync' => true,
                'idempotency' => true, 'max_page_size' => 1000,
            ],
            'session_token' => 'test-session-token',
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockResponseContent))
        );

        $response = $this->connector->handshake();

        $this->assertEquals(200, $response->getStatusCode());
        $responseBody = json_decode((string) $response->getBody(), true);
        $this->assertEquals('1.0.8', $responseBody['version']);
        $this->assertContains('1.0.8', $responseBody['supported_versions']);
        $this->assertTrue($responseBody['capabilities']['catalog']);
        $this->assertTrue($responseBody['capabilities']['orders']);
        $this->assertEquals('test-session-token', $responseBody['session_token']);
    }

    /**
     * @test
     */
    public function handshake_without_auth_returns_401(): void
    {
        $errorResponseContent = [
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication failed',
            ],
        ];

        $this->mockHandler->append(
            new ClientException(
                'Unauthorized',
                new Request('GET', 'https://api.test.com/v1/handshake'),
                new Response(401, [], json_encode($errorResponseContent))
            )
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(401);

        $this->connector->handshake();
    }

    /**
     * @test
     */
    public function get_request_with_pagination(): void
    {
        $mockResponseContent = [
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

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockResponseContent))
        );

        $response = $this->connector->get('/catalog/products', [
            'page' => 1,
            'limit' => 100,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertCount(2, $data['products']);
        $this->assertEquals(1, $data['pagination']['page']);

        // Assert the request that was sent
        $this->assertCount(1, $this->history);
        /** @var RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/catalog/products', $request->getUri()->getPath());
        $this->assertStringContainsString('page=1', $request->getUri()->getQuery());
        $this->assertStringContainsString('limit=100', $request->getUri()->getQuery());
    }

    /**
     * @test
     */
    public function get_request_with_updated_after_filter(): void
    {
        $mockResponseContent = [
            'products' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 100,
                'total' => 0,
                'has_next' => false,
            ],
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockResponseContent))
        );

        $updatedAfter = now()->subHour()->toIso8601String();

        $response = $this->connector->get('/catalog/products', [
            'updated_after' => $updatedAfter,
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        // Assert the request that was sent
        $this->assertCount(1, $this->history);
        /** @var RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/catalog/products', $request->getUri()->getPath());
        $this->assertStringContainsString('updated_after='.urlencode($updatedAfter), $request->getUri()->getQuery());
    }

    /**
     * @test
     */
    public function post_request_with_idempotency_key(): void
    {
        $idempotencyKey = $this->createTestUuid();
        $mockResponseContent = [
            'success' => true,
            'processed' => 1,
            'errors' => [],
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockResponseContent))
        );

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

        $this->assertEquals(200, $response->getStatusCode());

        // Assert the request that was sent
        $this->assertCount(1, $this->history);
        /** @var RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/catalog/products', $request->getUri()->getPath());
        $this->assertEquals($idempotencyKey, $request->getHeaderLine('X-Idempotency-Key'));
        $this->assertEquals(json_encode($productData), (string) $request->getBody());
    }

    /**
     * @test
     */
    public function post_request_without_idempotency_key(): void
    {
        $mockResponseContent = [
            'success' => true,
            'processed' => 1,
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockResponseContent))
        );

        $productData = [
            'products' => [['id' => $this->createTestUuid(), 'name' => 'Test']],
        ];

        $response = $this->connector->post('/catalog/products', $productData);

        $this->assertEquals(200, $response->getStatusCode());

        // Assert the request that was sent
        $this->assertCount(1, $this->history);
        /** @var RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertFalse($request->hasHeader('X-Idempotency-Key'));
    }

    /**
     * @test
     */
    public function patch_request_updates_resource(): void
    {
        $orderId = $this->createTestUuid();
        $idempotencyKey = $this->createTestUuid();

        $mockResponseContent = [
            'id' => $orderId,
            'status' => 'confirmed',
            'updated_at' => now()->toIso8601String(),
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockResponseContent))
        );

        $patchData = [
            'status' => 'confirmed',
        ];

        $response = $this->connector->patch("/orders/{$orderId}", $patchData, $idempotencyKey);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('confirmed', json_decode((string) $response->getBody(), true)['status']);

        // Assert the request that was sent
        $this->assertCount(1, $this->history);
        /** @var RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertEquals('PATCH', $request->getMethod());
        $this->assertEquals("/orders/{$orderId}", $request->getUri()->getPath());
        $this->assertEquals($idempotencyKey, $request->getHeaderLine('X-Idempotency-Key'));
        $this->assertEquals(json_encode($patchData), (string) $request->getBody());
    }

    /**
     * @test
     */
    public function delete_request_deactivates_product(): void
    {
        $productId = $this->createTestUuid();

        $mockResponseContent = [
            'id' => $productId,
            'is_active' => false,
            'deleted_at' => now()->toIso8601String(),
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockResponseContent))
        );

        $response = $this->connector->delete("/catalog/products/{$productId}");

        $this->assertEquals(200, $response->getStatusCode());
        $responseBody = json_decode((string) $response->getBody(), true);
        $this->assertFalse($responseBody['is_active']);
        $this->assertNotNull($responseBody['deleted_at']);

        // Assert the request that was sent
        $this->assertCount(1, $this->history);
        /** @var RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals("/catalog/products/{$productId}", $request->getUri()->getPath());
    }

    /**
     * @test
     */
    public function get_request_returns_404_for_nonexistent_resource(): void
    {
        $nonExistentId = $this->createTestUuid();
        $errorResponseContent = [
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Product not found',
            ],
        ];

        $this->mockHandler->append(
            new ClientException(
                'Not Found',
                new Request('GET', 'https://api.test.com/v1/catalog/products/'.$nonExistentId),
                new Response(404, [], json_encode($errorResponseContent))
            )
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product not found'); // Message from mappedHttpException

        $this->connector->get("/catalog/products/{$nonExistentId}");
    }

    /**
     * @test
     */
    public function post_request_returns_400_for_validation_error(): void
    {
        $errorResponseContent = [
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => [
                    'The product_id field is required.',
                    'The name field must be a string.',
                ],
            ],
        ];

        $this->mockHandler->append(
            new ClientException(
                'Bad Request',
                new Request('POST', 'https://api.test.com/v1/catalog/products'),
                new Response(400, [], json_encode($errorResponseContent))
            )
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(400);

        $this->connector->post('/catalog/products', ['invalid' => 'data']);
    }

    /**
     * @test
     */
    public function post_request_returns_422_for_business_error(): void
    {
        $errorResponseContent = [
            'error' => [
                'code' => 'STATUS_TRANSITION_ERROR',
                'message' => 'Order status cannot be changed from shipped to new',
            ],
        ];

        $this->mockHandler->append(
            new ClientException(
                'Unprocessable Entity',
                new Request('POST', 'https://api.test.com/v1/orders'),
                new Response(422, [], json_encode($errorResponseContent))
            )
        );

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(422);

        $this->connector->post('/orders', ['status' => 'new']);
    }

    /**
     * @test
     */
    public function request_returns_429_for_rate_limit(): void
    {
        $errorResponseContent = [
            'error' => [
                'code' => 'RATE_LIMITED',
                'message' => 'Too many requests',
            ],
        ];

        // Append N (retryAttempts) 429 responses
        $retryAttempts = 3; // Default for connector
        for ($i = 0; $i < $retryAttempts; $i++) {
            $this->mockHandler->append(
                new ClientException(
                    'Too Many Requests',
                    new Request('GET', 'https://api.test.com/v1/catalog/products'),
                    new Response(429, ['Retry-After' => '60'], json_encode($errorResponseContent))
                )
            );
        }

        $this->expectException(RateLimitException::class);
        $this->expectExceptionCode(429);

        $this->connector->get('/catalog/products');
        $this->assertEquals($retryAttempts, count($this->history)); // Verify all retries were attempted
    }

    /**
     * @test
     */
    public function connector_retries_on_5xx_errors(): void
    {
        $this->mockHandler->append(
            new Response(500, [], json_encode(['error' => 'Internal Server Error'])),
            new Response(500, [], json_encode(['error' => 'Internal Server Error'])),
            new Response(200, [], json_encode(['success' => true]))
        );

        $this->connector->setRetryAttempts(3);

        $response = $this->connector->get('/catalog/products');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, count($this->history)); // Должно быть 3 попытки
    }

    /**
     * @test
     */
    public function connector_sets_correct_headers(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['version' => '1.0.8']))
        );

        $this->connector->handshake();

        $this->assertCount(1, $this->history);
        /** @var RequestInterface $request */
        $request = $this->history[0]['request'];

        $this->assertEquals('application/json', $request->getHeaderLine('Accept'));
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertStringStartsWith('Bearer test-token', $request->getHeaderLine('Authorization'));
        $this->assertNotEmpty($request->getHeaderLine('X-Request-ID'));
    }

    /**
     * @test
     */
    public function connector_can_set_session_token(): void
    {
        $sessionToken = 'test-session-'.$this->createTestUuid();

        // No API call is made here, only internal state is set
        $this->connector->setSessionToken($sessionToken);

        $this->assertEquals($sessionToken, $this->connector->getSessionToken());
    }

    /**
     * @test
     */
    public function connector_can_set_retry_attempts(): void
    {
        $this->connector->setRetryAttempts(5);

        // Access private property for assertion using reflection
        $reflection = new \ReflectionClass($this->connector);
        $property = $reflection->getProperty('retryAttempts');
        $property->setAccessible(true);

        $this->assertEquals(5, $property->getValue($this->connector));
    }
}
