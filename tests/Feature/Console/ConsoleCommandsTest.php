<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature\Console;

use GeekCo\CommerceJson\Exceptions\AuthenticationException;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;

/**
 * Тесты для Console Commands
 *
 * @covers \GeekCo\CommerceJson\Console\Commands\HandshakeCommand
 * @covers \GeekCo\CommerceJson\Console\Commands\ImportClassifierCommand
 * @covers \GeekCo\CommerceJson\Console\Commands\ImportProductsCommand
 */
class ConsoleCommandsTest extends TestCase
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

    /**
     * @test
     */
    public function handshake_command_success(): void
    {
        $mockHandshakeResponse = $this->getJsonFixture('api-responses.json')['handshake_success'];

        $this->mockConnector->shouldReceive('handshake')
            ->once()
            ->andReturn(new Response(200, [], json_encode($mockHandshakeResponse)));

        $this->artisan('commercejson:handshake')
            ->expectsOutputToContain('Checking CommerceJSON API connection')
            ->expectsOutputToContain('Connected')
            ->expectsOutputToContain('Handshake completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function handshake_command_show_all(): void
    {
        $mockHandshakeResponse = $this->getJsonFixture('api-responses.json')['handshake_success'];

        $this->mockConnector->shouldReceive('handshake')
            ->once()
            ->andReturn(new Response(200, [], json_encode($mockHandshakeResponse)));

        $this->artisan('commercejson:handshake', ['--show-all' => true])
            ->expectsOutputToContain('Capabilities')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function handshake_command_unauthorized(): void
    {
        $errorResponse = [
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication failed',
            ],
        ];

        $this->mockConnector->shouldReceive('handshake')
            ->once()
            ->andThrow(new AuthenticationException(
                $errorResponse['error']['message'],
                401
            ));

        $this->artisan('commercejson:handshake')
            ->expectsOutputToContain('Authentication error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function import_classifier_command_success(): void
    {
        $mockHandshakeResponse = $this->getJsonFixture('api-responses.json')['handshake_success'];
        $mockClassifierResponse = $this->getJsonFixture('api-responses.json')['classifier_success'];

        $this->mockConnector->shouldReceive('handshake')
            ->atLeast()
            ->once()
            ->andReturn(new Response(200, [], json_encode($mockHandshakeResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/catalog/classifier', Mockery::any())
            ->andReturn(new Response(200, [], json_encode($mockClassifierResponse)));

        $this->artisan('commercejson:import-classifier')
            ->expectsOutputToContain('Starting classifier import')
            ->expectsOutputToContain('Fetching classifier from API')
            ->expectsOutputToContain('Syncing categories')
            ->expectsOutputToContain('Classifier import completed successfully')
            ->assertSuccessful();
    }

    /**
     * @test
     */
    public function import_classifier_command_no_sync(): void
    {
        $mockHandshakeResponse = $this->getJsonFixture('api-responses.json')['handshake_success'];
        $mockClassifierResponse = $this->getJsonFixture('api-responses.json')['classifier_success'];

        $this->mockConnector->shouldReceive('handshake')
            ->atLeast()
            ->once()
            ->andReturn(new Response(200, [], json_encode($mockHandshakeResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/catalog/classifier', Mockery::any())
            ->andReturn(new Response(200, [], json_encode($mockClassifierResponse)));

        $this->artisan('commercejson:import-classifier', ['--no-sync' => true])
            ->expectsOutputToContain('Skipping database sync')
            ->assertSuccessful();
    }

    /**
     * @test
     */
    public function import_products_command_success(): void
    {
        $mockHandshakeResponse = $this->getJsonFixture('api-responses.json')['handshake_success'];
        $mockProductsListResponse = $this->getJsonFixture('api-responses.json')['products_list'];

        $this->mockConnector->shouldReceive('handshake')
            ->atLeast()
            ->once()
            ->andReturn(new Response(200, [], json_encode($mockHandshakeResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/catalog/products', Mockery::subset(['page' => 1, 'limit' => 100]))
            ->andReturn(new Response(200, [], json_encode($mockProductsListResponse)));

        $this->artisan('commercejson:import-products', [
            '--page' => 1,
            '--limit' => 100,
        ])
            ->expectsOutputToContain('Starting products import')
            ->expectsOutputToContain('Fetching products')
            ->expectsOutputToContain('Syncing products')
            ->expectsOutputToContain('Products import completed successfully')
            ->assertSuccessful();
    }

    /**
     * @test
     */
    public function import_products_command_with_category_filter(): void
    {
        $categoryId = $this->createTestUuid();
        $mockHandshakeResponse = $this->getJsonFixture('api-responses.json')['handshake_success'];
        $mockProductsListResponse = $this->getJsonFixture('api-responses.json')['products_list'];

        $this->mockConnector->shouldReceive('handshake')
            ->atLeast()
            ->once()
            ->andReturn(new Response(200, [], json_encode($mockHandshakeResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/catalog/products', Mockery::subset(['category_id' => $categoryId]))
            ->andReturn(new Response(200, [], json_encode($mockProductsListResponse)));

        $this->artisan('commercejson:import-products', [
            '--category' => $categoryId,
        ])
            ->expectsOutputToContain("Category filter: {$categoryId}")
            ->assertSuccessful();
    }

    /**
     * @test
     */
    public function import_products_command_with_updated_after(): void
    {
        $updatedAfter = now()->subHour()->toIso8601String();
        $mockHandshakeResponse = $this->getJsonFixture('api-responses.json')['handshake_success'];
        $mockProductsListResponse = $this->getJsonFixture('api-responses.json')['products_list'];

        $this->mockConnector->shouldReceive('handshake')
            ->atLeast()
            ->once()
            ->andReturn(new Response(200, [], json_encode($mockHandshakeResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/catalog/products', Mockery::subset(['updated_after' => $updatedAfter]))
            ->andReturn(new Response(200, [], json_encode($mockProductsListResponse)));

        $this->artisan('commercejson:import-products', [
            '--updated-after' => $updatedAfter,
        ])
            ->expectsOutputToContain('Updated after:')
            ->assertSuccessful();
    }

    /**
     * @test
     */
    public function import_products_command_no_sync(): void
    {
        $mockHandshakeResponse = $this->getJsonFixture('api-responses.json')['handshake_success'];
        $mockProductsListResponse = $this->getJsonFixture('api-responses.json')['products_list'];

        $this->mockConnector->shouldReceive('handshake')
            ->atLeast()
            ->once()
            ->andReturn(new Response(200, [], json_encode($mockHandshakeResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/catalog/products', Mockery::any())
            ->andReturn(new Response(200, [], json_encode($mockProductsListResponse)));

        $this->artisan('commercejson:import-products', ['--no-sync' => true])
            ->expectsOutputToContain('Skipping database sync')
            ->assertSuccessful();
    }

    /**
     * @test
     */
    public function sync_command_full(): void
    {
        $mockHandshakeResponse = $this->getJsonFixture('api-responses.json')['handshake_success'];
        $mockClassifierResponse = $this->getJsonFixture('api-responses.json')['classifier_success'];
        $mockProductsListResponse = $this->getJsonFixture('api-responses.json')['products_list'];
        $mockOffersResponse = ['offers' => [], 'pagination' => ['has_next' => false]];
        $mockOrdersResponse = ['orders' => [], 'pagination' => ['has_next' => false]];

        $this->mockConnector->shouldReceive('handshake')
            ->atLeast()
            ->once()
            ->andReturn(new Response(200, [], json_encode($mockHandshakeResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/catalog/classifier', Mockery::any())
            ->andReturn(new Response(200, [], json_encode($mockClassifierResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/catalog/products', Mockery::any())
            ->andReturn(new Response(200, [], json_encode($mockProductsListResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/offers', Mockery::any())
            ->andReturn(new Response(200, [], json_encode($mockOffersResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/orders', Mockery::any())
            ->andReturn(new Response(200, [], json_encode($mockOrdersResponse)));

        $this->artisan('commercejson:sync', [
            '--full' => true,
            '--no-interaction' => true,
        ])
            ->expectsOutputToContain('Starting CommerceJSON sync')
            ->expectsOutputToContain('Syncing classifier')
            ->expectsOutputToContain('Syncing products')
            ->expectsOutputToContain('Sync completed successfully')
            ->assertSuccessful();
    }

    /**
     * @test
     */
    public function sync_command_incremental(): void
    {
        $since = now()->subHour()->toIso8601String();
        $mockHandshakeResponse = $this->getJsonFixture('api-responses.json')['handshake_success'];
        $mockProductsListResponse = $this->getJsonFixture('api-responses.json')['products_list'];
        $mockOffersResponse = ['offers' => [], 'pagination' => ['has_next' => false]];
        $mockOrdersResponse = ['orders' => [], 'pagination' => ['has_next' => false]];

        $this->mockConnector->shouldReceive('handshake')
            ->atLeast()
            ->once()
            ->andReturn(new Response(200, [], json_encode($mockHandshakeResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/catalog/products', Mockery::subset(['updated_after' => $since]))
            ->andReturn(new Response(200, [], json_encode($mockProductsListResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/offers', Mockery::subset(['updated_after' => $since]))
            ->andReturn(new Response(200, [], json_encode($mockOffersResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/orders', Mockery::subset(['updated_after' => $since]))
            ->andReturn(new Response(200, [], json_encode($mockOrdersResponse)));

        $this->artisan('commercejson:sync', [
            '--incremental' => true,
            '--since' => $since,
        ])
            ->expectsOutputToContain('Starting CommerceJSON sync')
            ->assertSuccessful();
    }

    /**
     * @test
     */
    public function sync_command_only_products(): void
    {
        $mockHandshakeResponse = $this->getJsonFixture('api-responses.json')['handshake_success'];
        $mockProductsListResponse = $this->getJsonFixture('api-responses.json')['products_list'];

        $this->mockConnector->shouldReceive('handshake')
            ->atLeast()
            ->once()
            ->andReturn(new Response(200, [], json_encode($mockHandshakeResponse)));

        $this->mockConnector->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with('/catalog/products', Mockery::any())
            ->andReturn(new Response(200, [], json_encode($mockProductsListResponse)));

        $this->artisan('commercejson:sync', ['--products' => true])
            ->expectsOutputToContain('Syncing products')
            ->assertSuccessful();
    }
}
