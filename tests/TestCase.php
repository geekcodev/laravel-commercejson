<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests;

use GeekCo\CommerceJson\CommerceJsonServiceProvider;
use GeekCo\CommerceJson\Database\Seeders\PriceTypeSeeder;
use GeekCo\CommerceJson\Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Базовый тестовый класс для CommerceJSON package
 */
abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    /**
     * Получить пакеты, которые нужно загрузить
     */
    protected function getPackageProviders($app): array
    {
        return [
            CommerceJsonServiceProvider::class,
        ];
    }

    /**
     * Настроить окружение перед тестом
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Настройка конфига для тестов
        $this->app['config']->set('commercejson.base_url', 'https://api.test.com/v1');
        $this->app['config']->set('commercejson.auth.token', 'test-token');
        $this->app['config']->set('commercejson.auth.type', 'bearer');
        $this->app['config']->set('commercejson.timeout', 30);
        $this->app['config']->set('commercejson.logging.enabled', false);
    }

    /**
     * Загрузить сидеры для тестов
     */
    protected function loadSeeders(array $seeders = []): void
    {
        $defaultSeeders = [
            PriceTypeSeeder::class,
            WarehouseSeeder::class,
        ];

        $seeders = empty($seeders) ? $defaultSeeders : $seeders;

        foreach ($seeders as $seeder) {
            $this->seed($seeder);
        }
    }

    /**
     * Создать тестовый токен
     */
    protected function createTestToken(): string
    {
        return 'test_'.bin2hex(random_bytes(32));
    }

    /**
     * Создать тестовый UUID
     */
    protected function createTestUuid(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Создать тестовую дату
     */
    protected function createTestDate(string $modifier = 'now'): string
    {
        return now()->modify($modifier)->toIso8601String();
    }

    /**
     * Получить фикс из fixtures
     */
    protected function getFixture(string $filename): string
    {
        $path = __DIR__.'/Fixtures/'.$filename;

        if (! file_exists($path)) {
            throw new \RuntimeException("Fixture not found: {$path}");
        }

        return file_get_contents($path);
    }

    /**
     * Получить JSON из fixtures
     */
    protected function getJsonFixture(string $filename): array
    {
        return json_decode($this->getFixture($filename), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Assert что UUID валидный
     */
    protected function assertValidUuid(string $uuid): void
    {
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $uuid,
            "Invalid UUID format: {$uuid}"
        );
    }

    /**
     * Assert что дата в ISO 8601 формате
     */
    protected function assertIso8601Date(string $date): void
    {
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $date,
            "Invalid ISO 8601 date format: {$date}"
        );
    }

    /**
     * Assert что сумма в правильном формате (строка с 2 знаками после запятой)
     */
    protected function assertMoneyFormat(string $amount): void
    {
        $this->assertMatchesRegularExpression(
            '/^\d+(\.\d{1,2})?$/',
            $amount,
            "Invalid money format: {$amount}"
        );
    }
}
