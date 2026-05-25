<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature\Console;

use GeekCo\CommerceJson\Http\Client\Dto\Response\ResponseDto;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Mockery;

class HandshakeCommandTest extends TestCase
{
    protected Mockery\MockInterface|HttpClientInterface $mockHttp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockHttp = Mockery::mock(HttpClientInterface::class);
        $this->app->instance(HttpClientInterface::class, $this->mockHttp);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function handshake_command_succeeds(): void
    {
        $mockResponse = [
            'version' => '1.0.8',
            'server_time' => now()->toIso8601String(),
            'capabilities' => ['catalog' => true, 'orders' => true],
        ];
        $responseDto = new ResponseDto(200, [], $mockResponse, new Psr7Response(200, [], json_encode($mockResponse)));

        $this->mockHttp->shouldReceive('get')->once()->with('/handshake', [])->andReturn($responseDto);

        $this->artisan('commercejson:handshake')
            ->expectsOutputToContain('Checking CommerceJSON API connection')
            ->expectsOutputToContain('Connected')
            ->assertExitCode(0);
    }

    /** @test */
    public function handshake_command_with_show_all_option(): void
    {
        $mockResponse = [
            'version' => '1.0.8',
            'server_time' => now()->toIso8601String(),
            'capabilities' => ['catalog' => true, 'offers' => false],
            'supported_versions' => ['1.0.8'],
        ];
        $responseDto = new ResponseDto(200, [], $mockResponse, new Psr7Response(200, [], json_encode($mockResponse)));

        $this->mockHttp->shouldReceive('get')->once()->with('/handshake', [])->andReturn($responseDto);

        $this->artisan('commercejson:handshake', ['--show-all' => true])
            ->expectsOutputToContain('Server Capabilities')
            ->assertExitCode(0);
    }

    /** @test */
    public function handshake_command_fails_on_connection_error(): void
    {
        $this->mockHttp->shouldReceive('get')
            ->once()
            ->with('/handshake', [])
            ->andThrow(new \Exception('Connection failed'));

        $this->artisan('commercejson:handshake')
            ->expectsOutputToContain('Connection failed')
            ->assertExitCode(1);
    }
}
