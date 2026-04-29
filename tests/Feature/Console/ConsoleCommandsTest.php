<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature\Console;

use GeekCo\CommerceJson\Tests\TestCase;
use Illuminate\Support\Facades\Http;

/**
 * Тесты для Console Commands
 *
 * @covers \GeekCo\CommerceJson\Console\Commands\HandshakeCommand
 * @covers \GeekCo\CommerceJson\Console\Commands\ImportClassifierCommand
 * @covers \GeekCo\CommerceJson\Console\Commands\ImportProductsCommand
 */
class ConsoleCommandsTest extends TestCase
{
    /**
     * @test
     */
    public function handshake_command_success(): void
    {
        Http::fake([
            '*/handshake' => Http::response($this->getJsonFixture('api-responses.json')['handshake_success'], 200),
        ]);

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
        Http::fake([
            '*/handshake' => Http::response($this->getJsonFixture('api-responses.json')['handshake_success'], 200),
        ]);

        $this->artisan('commercejson:handshake', ['--show-all' => true])
            ->expectsOutputToContain('Capabilities')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function handshake_command_unauthorized(): void
    {
        Http::fake([
            '*/handshake' => Http::response($this->getJsonFixture('api-responses.json')['handshake_unauthorized'], 401),
        ]);

        $this->artisan('commercejson:handshake')
            ->expectsOutputToContain('Authentication error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function import_classifier_command_success(): void
    {
        Http::fake([
            '*/handshake' => Http::response($this->getJsonFixture('api-responses.json')['handshake_success'], 200),
            '*/catalog/classifier' => Http::response($this->getJsonFixture('api-responses.json')['classifier_success'], 200),
        ]);

        $this->artisan('commercejson:import-classifier')
            ->expectsOutputToContain('Starting classifier import')
            ->expectsOutputToContain('Fetching classifier from API')
            ->expectsOutputToContain('Synchronizing categories')
            ->expectsOutputToContain('Classifier import completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function import_classifier_command_no_sync(): void
    {
        Http::fake([
            '*/handshake' => Http::response($this->getJsonFixture('api-responses.json')['handshake_success'], 200),
            '*/catalog/classifier' => Http::response($this->getJsonFixture('api-responses.json')['classifier_success'], 200),
        ]);

        $this->artisan('commercejson:import-classifier', ['--no-sync' => true])
            ->expectsOutputToContain('Skipping database sync')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function import_products_command_success(): void
    {
        Http::fake([
            '*/handshake' => Http::response($this->getJsonFixture('api-responses.json')['handshake_success'], 200),
            '*/catalog/products*' => Http::response($this->getJsonFixture('api-responses.json')['products_list'], 200),
        ]);

        $this->artisan('commercejson:import-products', [
            '--page' => 1,
            '--limit' => 100,
        ])
            ->expectsOutputToContain('Starting products import')
            ->expectsOutputToContain('Fetching products')
            ->expectsOutputToContain('Synchronizing products')
            ->expectsOutputToContain('Products import completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function import_products_command_with_category_filter(): void
    {
        $categoryId = $this->createTestUuid();

        Http::fake([
            '*/handshake' => Http::response($this->getJsonFixture('api-responses.json')['handshake_success'], 200),
            '*/catalog/products*' => Http::response($this->getJsonFixture('api-responses.json')['products_list'], 200),
        ]);

        $this->artisan('commercejson:import-products', [
            '--category' => $categoryId,
        ])
            ->expectsOutputToContain("Category filter: {$categoryId}")
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function import_products_command_with_updated_after(): void
    {
        $updatedAfter = now()->subHour()->toIso8601String();

        Http::fake([
            '*/handshake' => Http::response($this->getJsonFixture('api-responses.json')['handshake_success'], 200),
            '*/catalog/products*' => Http::response($this->getJsonFixture('api-responses.json')['products_list'], 200),
        ]);

        $this->artisan('commercejson:import-products', [
            '--updated-after' => $updatedAfter,
        ])
            ->expectsOutputToContain('Updated after:')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function import_products_command_no_sync(): void
    {
        Http::fake([
            '*/handshake' => Http::response($this->getJsonFixture('api-responses.json')['handshake_success'], 200),
            '*/catalog/products*' => Http::response($this->getJsonFixture('api-responses.json')['products_list'], 200),
        ]);

        $this->artisan('commercejson:import-products', ['--no-sync' => true])
            ->expectsOutputToContain('Skipping database sync')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function sync_command_full(): void
    {
        Http::fake([
            '*/handshake' => Http::response($this->getJsonFixture('api-responses.json')['handshake_success'], 200),
            '*/catalog/classifier' => Http::response($this->getJsonFixture('api-responses.json')['classifier_success'], 200),
            '*/catalog/products*' => Http::response($this->getJsonFixture('api-responses.json')['products_list'], 200),
            '*/offers*' => Http::response(['offers' => [], 'pagination' => ['has_next' => false]], 200),
            '*/orders*' => Http::response(['orders' => [], 'pagination' => ['has_next' => false]], 200),
        ]);

        $this->artisan('commercejson:sync', [
            '--full' => true,
            '--no-interaction' => true,
        ])
            ->expectsOutputToContain('Starting CommerceJSON sync')
            ->expectsOutputToContain('Synchronizing classifier')
            ->expectsOutputToContain('Synchronizing products')
            ->expectsOutputToContain('Sync completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function sync_command_incremental(): void
    {
        $since = now()->subHour()->toIso8601String();

        Http::fake([
            '*/handshake' => Http::response($this->getJsonFixture('api-responses.json')['handshake_success'], 200),
            '*/catalog/products*' => Http::response($this->getJsonFixture('api-responses.json')['products_list'], 200),
            '*/offers*' => Http::response(['offers' => [], 'pagination' => ['has_next' => false]], 200),
            '*/orders*' => Http::response(['orders' => [], 'pagination' => ['has_next' => false]], 200),
        ]);

        $this->artisan('commercejson:sync', [
            '--incremental' => true,
            '--since' => $since,
        ])
            ->expectsOutputToContain('INCREMENTAL')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function sync_command_only_products(): void
    {
        Http::fake([
            '*/handshake' => Http::response($this->getJsonFixture('api-responses.json')['handshake_success'], 200),
            '*/catalog/products*' => Http::response($this->getJsonFixture('api-responses.json')['products_list'], 200),
        ]);

        $this->artisan('commercejson:sync', ['--products' => true])
            ->expectsOutputToContain('Synchronizing products')
            ->assertExitCode(0);
    }
}
