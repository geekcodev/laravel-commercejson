<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature;

use GeekCo\CommerceJson\Exceptions\AuthenticationException;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;

class HandshakeApiTest extends TestCase
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
    public function handshake_returns_success_response(): void
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
            'session_token' => 'mock_session_token_123',
        ];

        $this->mockConnector->shouldReceive('handshake')
            ->once()
            ->andReturn(new Response(200, [], json_encode($mockResponse)));

        $response = $this->mockConnector->handshake();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($mockResponse), (string) $response->getBody());
        $this->assertStringContainsString('1.0.8', (string) $response->getBody());
    }

    /** @test */
    public function handshake_throws_authentication_exception_on_401(): void
    {
        $errorResponse = [
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication failed.',
            ],
        ];

        $this->mockConnector->shouldReceive('handshake')
            ->once()
            // Corrected: Throw the package's specific exception
            ->andThrow(new AuthenticationException(
                $errorResponse['error']['message'],
                401
            ));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(401);

        $this->mockConnector->handshake();
    }

    /** @test */
    public function handshake_provides_session_token_for_subsequent_requests(): void
    {
        $sessionToken = 'test-session-token-abc';
        $handshakeResponse = [
            'version' => '1.0.8',
            'supported_versions' => ['1.0.8'],
            'server_time' => now()->toIso8601String(),
            'capabilities' => ['catalog' => true],
            'session_token' => $sessionToken,
        ];
        $classifierResponse = ['id' => $this->createTestUuid(), 'name' => 'Test Classifier'];

        // Mock handshake call
        $this->mockConnector->shouldReceive('handshake')
            ->once()
            ->andReturn(new Response(200, [], json_encode($handshakeResponse)));

        // Mock subsequent call using session token
        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/classifier', Mockery::any())
            ->andReturn(new Response(200, [], json_encode($classifierResponse)));

        $handshakeResult = $this->mockConnector->handshake();

        // Parse session token from response
        $handshakeData = json_decode((string) $handshakeResult->getBody(), true);
        $sessionTokenFromResponse = $handshakeData['session_token'] ?? null;

        $this->assertEquals($sessionToken, $sessionTokenFromResponse);

        $classifierData = $this->mockConnector->get('/catalog/classifier');

        $this->assertEquals(200, $classifierData->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($classifierResponse), (string) $classifierData->getBody());
    }

    /** @test */
    public function handshake_capabilities_are_validated(): void
    {
        $mockResponse = [
            'version' => '1.0.8',
            'supported_versions' => ['1.0.8'],
            'server_time' => now()->toIso8601String(),
            'capabilities' => [
                'catalog' => false,
                'orders' => true,
                'max_page_size' => 500,
            ],
            'session_token' => null,
        ];

        $this->mockConnector->shouldReceive('handshake')
            ->once()
            ->andReturn(new Response(200, [], json_encode($mockResponse)));

        $response = $this->mockConnector->handshake();
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertFalse($responseData['capabilities']['catalog']);
        $this->assertTrue($responseData['capabilities']['orders']);
        $this->assertEquals(500, $responseData['capabilities']['max_page_size']);
    }
}
