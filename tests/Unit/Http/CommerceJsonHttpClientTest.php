<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Http;

use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Http\Client\CommerceJsonHttpClient;
use GeekCo\CommerceJson\Http\Client\Dto\Response\ResponseDto;
use GeekCo\CommerceJson\Http\Client\Exceptions\AuthenticationException;
use GeekCo\CommerceJson\Http\Client\Exceptions\BusinessException;
use GeekCo\CommerceJson\Http\Client\Exceptions\RateLimitException;
use GeekCo\CommerceJson\Http\Client\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Client\NoDelayRetryStrategy;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CommerceJsonHttpClient::class)]
class CommerceJsonHttpClientTest extends TestCase
{
    protected CommerceJsonHttpClient $http;

    protected MockHandler $mockHandler;

    protected array $history = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHandler = new MockHandler;
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push(Middleware::history($this->history));

        $mockGuzzleClient = new Client(['handler' => $handlerStack]);

        $this->http = new CommerceJsonHttpClient(
            baseUrl: 'https://api.test.com/v1',
            authToken: 'test-token',
            timeout: 30,
            authType: 'bearer',
            retryStrategy: new NoDelayRetryStrategy(maxAttempts: 3)
        );

        // Inject mock Guzzle client via reflection
        $reflection = new \ReflectionClass($this->http);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->http, $mockGuzzleClient);
    }

    protected function tearDown(): void
    {
        $this->history = [];
        parent::tearDown();
    }

    public function test_handshake_request_returns_response(): void
    {
        $mockResponse = [
            'version' => '1.0.8',
            'server_time' => now()->toIso8601String(),
            'capabilities' => ['catalog' => true, 'orders' => true],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $response = $this->http->get('/handshake');

        $this->assertInstanceOf(ResponseDto::class, $response);
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('1.0.8', $response->data['version']);
        $this->assertTrue($response->data['capabilities']['catalog']);
    }

    public function test_handshake_without_auth_returns_401(): void
    {
        $errorResponse = ['error' => ['message' => 'Authentication failed']];

        $this->mockHandler->append(
            new ClientException(
                'Unauthorized',
                new Request('GET', 'https://api.test.com/v1/handshake'),
                new Response(401, [], json_encode($errorResponse))
            )
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(401);

        $this->http->get('/handshake');
    }

    public function test_get_request_with_query_params(): void
    {
        $mockResponse = [
            'products' => [['id' => $this->createTestUuid(), 'name' => 'Product 1']],
            'pagination' => ['page' => 1, 'limit' => 100, 'total' => 1, 'has_next' => false],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $response = $this->http->get('/catalog/products', ['page' => 1, 'limit' => 100]);

        $this->assertEquals(200, $response->statusCode);
        $this->assertCount(1, $response->data['products']);

        // Assert request
        $this->assertCount(1, $this->history);
        $request = $this->history[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertStringContainsString('page=1', $request->getUri()->getQuery());
    }

    public function test_post_request_with_idempotency_key(): void
    {
        $idempotencyKey = $this->createTestUuid();
        $mockResponse = ['success' => true, 'processed' => 1];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $data = ['products' => [['id' => $this->createTestUuid(), 'name' => 'Test']]];
        $response = $this->http->post('/catalog/products', $data, $idempotencyKey);

        $this->assertEquals(200, $response->statusCode);

        // Assert request
        $this->assertCount(1, $this->history);
        $request = $this->history[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($idempotencyKey, $request->getHeaderLine('X-Idempotency-Key'));
    }

    public function test_patch_request_updates_resource(): void
    {
        $orderId = $this->createTestUuid();
        $mockResponse = ['id' => $orderId, 'status' => OrderStatusEnum::Confirmed->value];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $response = $this->http->patch("/orders/{$orderId}", ['status' => OrderStatusEnum::Confirmed->value]);

        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals(OrderStatusEnum::Confirmed->value, $response->data['status']);
    }

    public function test_delete_request(): void
    {
        $productId = $this->createTestUuid();
        $mockResponse = ['id' => $productId, 'deleted' => true];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $response = $this->http->delete("/catalog/products/{$productId}");

        $this->assertEquals(200, $response->statusCode);
        $this->assertTrue($response->data['deleted']);
    }

    public function test_returns_404_for_nonexistent_resource(): void
    {
        $errorResponse = ['error' => ['message' => 'Not found']];

        $this->mockHandler->append(
            new ClientException(
                'Not Found',
                new Request('GET', 'https://api.test.com/v1/catalog/products/123'),
                new Response(404, [], json_encode($errorResponse))
            )
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not found');
        $this->expectExceptionCode(404);

        $this->http->get('/catalog/products/123');
    }

    public function test_returns_400_for_validation_error(): void
    {
        $errorResponse = [
            'error' => [
                'message' => 'Validation failed',
                'details' => ['The name field is required.'],
            ],
        ];

        $this->mockHandler->append(
            new ClientException(
                'Bad Request',
                new Request('POST', 'https://api.test.com/v1/catalog/products'),
                new Response(400, [], json_encode($errorResponse))
            )
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(400);

        $this->http->post('/catalog/products', ['name' => '']);
    }

    public function test_returns_422_for_business_error(): void
    {
        $errorResponse = ['error' => ['message' => 'Business logic error']];

        $this->mockHandler->append(
            new ClientException(
                'Unprocessable Entity',
                new Request('POST', 'https://api.test.com/v1/orders'),
                new Response(422, [], json_encode($errorResponse))
            )
        );

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(422);

        $this->http->post('/orders', ['status' => 'invalid']);
    }

    public function test_returns_429_for_rate_limit(): void
    {
        $errorResponse = ['error' => ['message' => 'Rate limit exceeded']];

        // Mock 4 retry attempts (maxAttempts = 3, so 1 initial + 3 retries = 4)
        for ($i = 0; $i < 4; $i++) {
            $this->mockHandler->append(
                new ClientException(
                    'Too Many Requests',
                    new Request('GET', 'https://api.test.com/v1/catalog/products'),
                    new Response(429, ['Retry-After' => '60'], json_encode($errorResponse))
                )
            );
        }

        $this->expectException(RateLimitException::class);
        $this->expectExceptionCode(429);

        try {
            $this->http->get('/catalog/products');
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            // Verify 4 attempts were made (1 initial + 3 retries)
            $this->assertCount(4, $this->history);
            throw $e;
        }
    }

    public function test_retries_on_5xx_errors(): void
    {
        $this->mockHandler->append(
            new Response(500, [], json_encode(['error' => 'Internal Server Error'])),
            new Response(500, [], json_encode(['error' => 'Internal Server Error'])),
            new Response(200, [], json_encode(['success' => true]))
        );

        $response = $this->http->get('/catalog/products');

        $this->assertEquals(200, $response->statusCode);
        $this->assertCount(3, $this->history); // 3 attempts
    }

    public function test_sets_correct_headers(): void
    {
        $this->mockHandler->append(new Response(200, [], json_encode(['version' => '1.0.8'])));

        $this->http->get('/handshake');

        $this->assertCount(1, $this->history);
        $request = $this->history[0]['request'];

        $this->assertEquals('application/json', $request->getHeaderLine('Accept'));
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertStringStartsWith('Bearer test-token', $request->getHeaderLine('Authorization'));
        $this->assertNotEmpty($request->getHeaderLine('X-Request-ID'));
    }
}
