<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests;

use GeekCo\CommerceJson\CommerceJsonServiceProvider;
use GeekCo\CommerceJson\Data\AddressData;
use GeekCo\CommerceJson\Data\CategoryData;
use GeekCo\CommerceJson\Data\OfferData;
use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Data\PriceTypeData;
use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Data\ProductListData;
use GeekCo\CommerceJson\Data\WarehouseData;
use GeekCo\CommerceJson\Database\Seeders\PriceTypeSeeder;
use GeekCo\CommerceJson\Database\Seeders\WarehouseSeeder;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Normalizers\ArrayableNormalizer;
use Spatie\LaravelData\Normalizers\ArrayNormalizer;
use Spatie\LaravelData\Normalizers\JsonNormalizer;
use Spatie\LaravelData\Normalizers\ModelNormalizer;
use Spatie\LaravelData\Normalizers\ObjectNormalizer;
use Spatie\LaravelData\RuleInferrers\AttributesRuleInferrer;
use Spatie\LaravelData\RuleInferrers\BuiltInTypesRuleInferrer;
use Spatie\LaravelData\RuleInferrers\NullableRuleInferrer;
use Spatie\LaravelData\RuleInferrers\RequiredRuleInferrer;
use Spatie\LaravelData\RuleInferrers\SometimesRuleInferrer;
use Spatie\LaravelData\Support\Creation\ValidationStrategy;
use Spatie\LaravelData\Transformers\ArrayableTransformer;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;
use Spatie\LaravelData\Transformers\EnumTransformer;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            CommerceJsonServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('data', [
            'date_format' => [
                'Y-m-d H:i:s',
                DATE_ATOM,
                'Y-m-d\\TH:i:s',
                'Y-m-d\\TH:i:s\\Z',
                'Y-m-d\\TH:i:s.u\\Z',
                'Y-m-d\\TH:i:s.uP',
                'Y-m-d\\TH:i:s.v\\Z',
                'Y-m-d\\TH:i:s.vP',
                'Y-m-d',
            ],
            'date_timezone' => null,
            'features' => [
                'cast_and_transform_iterables' => false,
                'ignore_exception_when_trying_to_set_computed_property_value' => false,
            ],
            'transformers' => [
                \DateTimeInterface::class => DateTimeInterfaceTransformer::class,
                Arrayable::class => ArrayableTransformer::class,
                \BackedEnum::class => EnumTransformer::class,
            ],
            'casts' => [
                \DateTimeInterface::class => DateTimeInterfaceCast::class,
                \BackedEnum::class => EnumCast::class,
            ],
            'rule_inferrers' => [
                SometimesRuleInferrer::class,
                NullableRuleInferrer::class,
                RequiredRuleInferrer::class,
                BuiltInTypesRuleInferrer::class,
                AttributesRuleInferrer::class,
            ],
            'normalizers' => [
                ModelNormalizer::class,
                ArrayableNormalizer::class,
                ObjectNormalizer::class,
                ArrayNormalizer::class,
                JsonNormalizer::class,
            ],
            'wrap' => null,
            'var_dumper_caster_mode' => 'development',
            'structure_caching' => [
                'enabled' => false,
                'directories' => [],
                'cache' => [
                    'store' => 'array',
                    'prefix' => 'laravel-data',
                    'duration' => null,
                ],
                'reflection_discovery' => [
                    'enabled' => false,
                ],
            ],
            'validation_strategy' => ValidationStrategy::OnlyRequests->value,
            'name_mapping_strategy' => [
                'input' => SnakeCaseMapper::class,
                'output' => SnakeCaseMapper::class,
            ],
            'ignore_invalid_partials' => false,
            'max_transformation_depth' => null,
            'throw_when_max_transformation_depth_reached' => true,
            'commands' => [
                'make' => [
                    'namespace' => 'Data',
                    'suffix' => 'Data',
                ],
            ],
            'livewire' => [
                'enable_synths' => false,
            ],
        ]);
        $app['config']->set('commercejson.api_routes.middleware', []);
        $app['config']->set('commercejson.base_url', 'https://api.test.com/v1');
        $app['config']->set('commercejson.auth.token', 'test-token');
        $app['config']->set('commercejson.auth.type', 'bearer');
        $app['config']->set('commercejson.timeout', 30);
        $app['config']->set('commercejson.logging.enabled', false);
    }

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

    protected function createTestToken(): string
    {
        return 'test_'.bin2hex(random_bytes(32));
    }

    protected function createTestUuid(): string
    {
        return (string) Str::uuid();
    }

    protected function createTestDate(string $modifier = 'now'): string
    {
        return now()->modify($modifier)->toIso8601String();
    }

    protected function getFixture(string $filename): string
    {
        $path = __DIR__.'/Fixtures/'.$filename;

        if (! file_exists($path)) {
            throw new \RuntimeException("Fixture not found: {$path}");
        }

        return file_get_contents($path);
    }

    protected function getJsonFixture(string $filename): array
    {
        return json_decode($this->getFixture($filename), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function createProductData(array $attributes = []): ProductData
    {
        return ProductData::factory()->from([
            'id' => $attributes['id'] ?? $this->createTestUuid(),
            'external_id' => $attributes['external_id'] ?? 'EXT-'.bin2hex(random_bytes(4)),
            'name' => $attributes['name'] ?? 'Test Product',
            'code' => $attributes['code'] ?? 'TEST-001',
            'barcode' => $attributes['barcode'] ?? null,
            'category_id' => $attributes['category_id'] ?? $this->createTestUuid(),
            'is_active' => $attributes['is_active'] ?? true,
        ]);
    }

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

    protected function createOrderData(array $attributes = []): OrderData
    {
        return OrderData::factory()->from([
            'id' => $attributes['id'] ?? $this->createTestUuid(),
            'number' => $attributes['number'] ?? 'ORD-001',
            'status' => $attributes['status'] ?? OrderStatusEnum::New->value,
            'items' => $attributes['items'] ?? [
                [
                    'id' => $this->createTestUuid(),
                    'product_id' => $this->createTestUuid(),
                    'quantity' => 1,
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
            'totals' => $attributes['totals'] ?? [
                'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
            ],
        ]);
    }

    protected function createCategoryData(array $attributes = []): CategoryData
    {
        return CategoryData::factory()->from([
            'id' => $attributes['id'] ?? $this->createTestUuid(),
            'name' => $attributes['name'] ?? 'Test Category',
            'parent_id' => $attributes['parent_id'] ?? null,
            'code' => $attributes['code'] ?? 'CAT-001',
        ]);
    }

    protected function createOfferData(array $attributes = []): OfferData
    {
        return OfferData::factory()->from([
            'product_id' => $attributes['product_id'] ?? $this->createTestUuid(),
            'prices' => $attributes['prices'] ?? [
                [
                    'price_type_id' => $this->createTestUuid(),
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
        ]);
    }

    protected function createPriceTypeData(array $attributes = []): PriceTypeData
    {
        return PriceTypeData::factory()->from([
            'id' => $attributes['id'] ?? $this->createTestUuid(),
            'name' => $attributes['name'] ?? 'Retail',
            'currency' => $attributes['currency'] ?? CurrencyEnum::RUB,
            'description' => $attributes['description'] ?? null,
            'is_default' => $attributes['is_default'] ?? false,
        ]);
    }

    protected function createWarehouseData(array $attributes = []): WarehouseData
    {
        return WarehouseData::factory()->from([
            'id' => $attributes['id'] ?? $this->createTestUuid(),
            'external_id' => $attributes['external_id'] ?? null,
            'name' => $attributes['name'] ?? 'Main Warehouse',
            'code' => $attributes['code'] ?? 'WH-001',
            'is_active' => $attributes['is_active'] ?? true,
            'is_default' => $attributes['is_default'] ?? false,
        ]);
    }

    protected function createAddressData(array $attributes = []): AddressData
    {
        return AddressData::factory()->from([
            'country' => $attributes['country'] ?? 'RU',
            'city' => $attributes['city'] ?? 'Moscow',
            'street' => $attributes['street'] ?? 'Lenina St.',
            'building' => $attributes['building'] ?? '1',
        ]);
    }
}
