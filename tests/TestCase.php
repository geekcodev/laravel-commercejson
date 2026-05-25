<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests;

use GeekCo\CommerceJson\CommerceJsonServiceProvider;
use GeekCo\CommerceJson\Data\CategoryData;
use GeekCo\CommerceJson\Data\CounterpartyData;
use GeekCo\CommerceJson\Data\OfferData;
use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Data\ProductListData;
use GeekCo\CommerceJson\Database\Seeders\PriceTypeSeeder;
use GeekCo\CommerceJson\Database\Seeders\WarehouseSeeder;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Http\Client\NoDelayRetryStrategy;
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

        // Устанавливаем стратегию retry без задержек для тестов
        $this->app->extend(HttpClientInterface::class, function ($http) {
            if (method_exists($http, 'setRetryStrategy')) {
                $http->setRetryStrategy(new NoDelayRetryStrategy(maxAttempts: 3));
            }

            return $http;
        });
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

    /**
     * Создать тестовые данные для ProductData
     */
    protected function createProductData(array $attributes = []): ProductData
    {
        return ProductData::factory()->from([
            'id' => $attributes['id'] ?? $this->createTestUuid(),
            'external_id' => $attributes['external_id'] ?? 'EXT-'.bin2hex(random_bytes(4)),
            'name' => $attributes['name'] ?? 'Test Product',
            'code' => $attributes['code'] ?? 'TEST-001',
            'barcode' => $attributes['barcode'] ?? null,
            'category_id' => $attributes['category_id'] ?? null,
            'is_active' => $attributes['is_active'] ?? true,
        ]);
    }

    /**
     * Создать тестовые данные для ProductListData
     */
    protected function createProductListData(array $products = [], array $pagination = []): ProductListData
    {
        return ProductListData::factory()->from([
            'products' => $products,
            'pagination' => [
                'page' => $pagination['page'] ?? 1,
                'limit' => $pagination['limit'] ?? 100,
                'total' => $pagination['total'] ?? 0,
                'has_next' => $pagination['has_next'] ?? false,
            ],
        ]);
    }

    /**
     * Создать тестовые данные для OrderData
     */
    protected function createOrderData(array $attributes = []): OrderData
    {
        return OrderData::factory()->from([
            'id' => $attributes['id'] ?? $this->createTestUuid(),
            'number' => $attributes['number'] ?? 'ORD-001',
            'status' => $attributes['status'] ?? 'new',
            'document_type' => $attributes['document_type'] ?? 'order',
            'counterparty_id' => $attributes['counterparty_id'] ?? null,
            'warehouse_id' => $attributes['warehouse_id'] ?? null,
        ]);
    }

    /**
     * Создать тестовые данные для CategoryData
     */
    protected function createCategoryData(array $attributes = []): CategoryData
    {
        return CategoryData::factory()->from([
            'id' => $attributes['id'] ?? $this->createTestUuid(),
            'name' => $attributes['name'] ?? 'Test Category',
            'parent_id' => $attributes['parent_id'] ?? null,
            'code' => $attributes['code'] ?? 'CAT-001',
        ]);
    }

    /**
     * Создать тестовые данные для OfferData
     */
    protected function createOfferData(array $attributes = []): OfferData
    {
        return OfferData::factory()->from([
            'id' => $attributes['id'] ?? $this->createTestUuid(),
            'product_id' => $attributes['product_id'] ?? $this->createTestUuid(),
            'price' => $attributes['price'] ?? 100.00,
            'currency' => $attributes['currency'] ?? 'RUB',
            'warehouse_id' => $attributes['warehouse_id'] ?? null,
            'price_type_id' => $attributes['price_type_id'] ?? null,
        ]);
    }

    /**
     * Создать тестовые данные для CounterpartyData
     */
    protected function createCounterpartyData(array $attributes = []): CounterpartyData
    {
        return CounterpartyData::factory()->from([
            'id' => $attributes['id'] ?? $this->createTestUuid(),
            'name' => $attributes['name'] ?? 'Test Company',
            'type' => $attributes['type'] ?? 'legal',
            'inn' => $attributes['inn'] ?? '1234567890',
            'kpp' => $attributes['kpp'] ?? null,
        ]);
    }
}
