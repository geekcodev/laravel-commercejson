<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Seeders;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\PropertyTypeEnum;
use GeekCo\CommerceJson\Models\Category;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\PriceType;
use GeekCo\CommerceJson\Models\PropertyDefinition;
use GeekCo\CommerceJson\Models\Warehouse;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Профессиональный сидер для нагрузочного тестирования.
 *
 * Генерирует большой, правдоподобный набор данных:
 * категории → товары → варианты → офферы → цены/остатки, изображения и свойства.
 *
 * Управляется env-переменными (все опциональны):
 * - COMMERCEJSON_SEED_SEED (default 1234)
 * - COMMERCEJSON_SEED_CHUNK (default 1000)
 * - COMMERCEJSON_SEED_EXTRA_CATEGORIES (default 5000)
 * - COMMERCEJSON_SEED_EXTRA_COUNTERPARTIES (default 200)
 * - COMMERCEJSON_SEED_PRODUCTS (default 20000)
 * - COMMERCEJSON_SEED_VARIANTS_RATIO (default 0.35)
 * - COMMERCEJSON_SEED_VARIANTS_MIN (default 2)
 * - COMMERCEJSON_SEED_VARIANTS_MAX (default 5)
 * - COMMERCEJSON_SEED_PRICE_TIERS (default 2) // 1=только min_quantity=1, 2=добавить tier для min_quantity=10
 * - COMMERCEJSON_SEED_STOCKS_PER_OFFER (default 1) // 1=только склад по умолчанию, 0=без остатков, N>1=несколько складов
 * - COMMERCEJSON_SEED_PROPERTIES (default 40)
 * - COMMERCEJSON_SEED_PRODUCT_PROPERTIES (default 6)
 * - COMMERCEJSON_SEED_VARIANT_PROPERTIES (default 3)
 * - COMMERCEJSON_SEED_ORDERS (default 0)
 */
class LoadTestDatabaseSeeder extends Seeder
{
    private string $runKey = 'LD';

    public function run(): void
    {
        $seed = (int) config('commercejson.seeding.load.seed', 1234);
        // Уменьшен по умолчанию для PostgreSQL (лимит 65535 параметров на запрос)
        // 1000 товаров × 8 полей = 8000 параметров, что безопасно
        $chunkSize = max(100, (int) config('commercejson.seeding.load.chunk', 500));

        $extraCategories = max(0, (int) config('commercejson.seeding.load.extra_categories', 5000));
        $extraCounterparties = max(0, (int) config('commercejson.seeding.load.extra_counterparties', 200));

        $productsCount = max(0, (int) config('commercejson.seeding.load.products', 20000));
        $variantsRatio = (float) config('commercejson.seeding.load.variants_ratio', 0.35);
        $variantsMin = max(0, (int) config('commercejson.seeding.load.variants_min', 2));
        $variantsMax = max($variantsMin, (int) config('commercejson.seeding.load.variants_max', 5));

        $priceTiers = max(1, (int) config('commercejson.seeding.load.price_tiers', 2));
        $stocksPerOffer = max(0, (int) config('commercejson.seeding.load.stocks_per_offer', 1));

        $propertiesCount = max(0, (int) config('commercejson.seeding.load.properties', 40));
        $productPropertiesPerEntity = max(0, (int) config('commercejson.seeding.load.product_properties', 6));
        $variantPropertiesPerEntity = max(0, (int) config('commercejson.seeding.load.variant_properties', 3));

        $ordersCount = max(0, (int) config('commercejson.seeding.load.orders', 0));

        fake()->seed($seed);
        $configuredRunKey = (string) config('commercejson.seeding.load.run_key', '');
        $this->runKey = $configuredRunKey !== ''
            ? $configuredRunKey
            : strtoupper(base_convert((string) ($seed % 1679616), 10, 36));
        if ($this->runKey === '') {
            $this->runKey = 'LD';
        }
        $now = now();

        $this->command?->info('🌱 Starting load-test database seeding...');

        $this->call([
            PriceTypeSeeder::class,
            WarehouseSeeder::class,
            CategorySeeder::class,
            CounterpartySeeder::class,
        ]);

        // PostgreSQL строго валидирует uuid. Если в справочниках остались "псевдо-id" — дальше всё будет падать.
        $this->assertUuidColumn('price_types', 'id');
        $this->assertUuidColumn('warehouses', 'id');
        $this->assertUuidColumn('categories', 'id');
        $this->assertUuidColumn('counterparties', 'id');

        if ($extraCounterparties > 0) {
            $this->seedExtraCounterparties($extraCounterparties, $chunkSize, $now, $seed);
        }

        if ($extraCategories > 0) {
            $this->seedExtraCategories($extraCategories, $chunkSize, $now);
        }

        $priceTypes = PriceType::query()->orderByDesc('is_default')->get(['id', 'currency']);
        $warehouses = Warehouse::query()->orderByDesc('is_default')->get(['id']);
        $defaultWarehouseId = $warehouses->first()?->id;

        if ($defaultWarehouseId === null) {
            throw new \RuntimeException('No warehouses found. WarehouseSeeder must create at least one warehouse.');
        }

        $categoryIds = Category::query()->pluck('id')->all();
        $manufacturerIds = Counterparty::query()->pluck('id')->all();

        if ($propertiesCount > 0) {
            $this->seedPropertyDefinitions($propertiesCount, $now);
        }

        $propertyDefinitions = PropertyDefinition::query()->get([
            'id',
            'type',
            'enum_values',
        ]);

        if ($productsCount > 0) {
            $this->seedCatalog(
                productsCount: $productsCount,
                categoryIds: $categoryIds,
                manufacturerIds: $manufacturerIds,
                priceTypes: $priceTypes->all(),
                warehouseIds: $warehouses->pluck('id')->all(),
                defaultWarehouseId: $defaultWarehouseId,
                propertyDefinitions: $propertyDefinitions->all(),
                variantsRatio: $variantsRatio,
                variantsMin: $variantsMin,
                variantsMax: $variantsMax,
                priceTiers: $priceTiers,
                stocksPerOffer: $stocksPerOffer,
                productPropertiesPerEntity: $productPropertiesPerEntity,
                variantPropertiesPerEntity: $variantPropertiesPerEntity,
                chunkSize: $chunkSize,
                now: $now
            );
        }

        if ($ordersCount > 0) {
            $this->command?->warn('Orders seeding is not implemented in LoadTestDatabaseSeeder yet (COMMERCEJSON_SEED_ORDERS is ignored).');
        }

        $this->command?->info('✅ Load-test database seeding completed!');
        $this->command?->info(sprintf(
            '   Totals: %d categories, %d products, %d offers, %d offer_prices, %d stocks',
            DB::table('categories')->count(),
            DB::table('products')->count(),
            DB::table('offers')->count(),
            DB::table('offer_prices')->count(),
            DB::table('stocks')->count(),
        ));
    }

    private function seedExtraCategories(int $count, int $chunkSize, Carbon $now): void
    {
        $this->command?->info(" - Seeding {$count} extra categories...");

        $existingIds = Category::query()->pluck('id')->all();
        $parentPool = $existingIds;

        $buffer = [];

        for ($i = 1; $i <= $count; $i++) {
            $id = (string) Str::uuid();

            $parentId = null;
            if (! empty($parentPool) && fake()->boolean(80)) {
                $parentId = $parentPool[array_rand($parentPool)];
            }

            $name = 'Категория '.str_pad((string) $i, 7, '0', STR_PAD_LEFT);

            $buffer[] = [
                'id' => $id,
                'parent_id' => $parentId,
                'name' => $name,
                'code' => 'CAT-LD-'.$this->runKey.'-'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'sort' => $i,
                'is_active' => fake()->boolean(97),
                'image_url' => fake()->boolean(30) ? fake()->imageUrl() : null,
                'seo_title' => "Купить {$name} - интернет-магазин",
                'seo_description' => fake()->sentence(20),
                'seo_keywords' => implode(', ', fake()->words(10)),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $parentPool[] = $id;

            if (count($buffer) >= $chunkSize) {
                DB::table('categories')->insertOrIgnore($buffer);
                $buffer = [];
            }
        }

        if (! empty($buffer)) {
            DB::table('categories')->insertOrIgnore($buffer);
        }
    }

    private function assertUuidColumn(string $table, string $column): void
    {
        $invalid = DB::table($table)
            ->whereNotNull($column)
            ->whereRaw("{$column}::text !~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'")
            ->limit(5)
            ->pluck($column)
            ->all();

        if (! empty($invalid)) {
            throw new \RuntimeException(sprintf(
                'Invalid UUID values detected in %s.%s: %s',
                $table,
                $column,
                implode(', ', array_map(fn ($v) => (string) $v, $invalid))
            ));
        }
    }

    private function seedExtraCounterparties(int $count, int $chunkSize, Carbon $now, int $seed): void
    {
        $this->command?->info(" - Seeding {$count} extra counterparties...");

        // Без фабрик: быстрый bulk insert для производителей/контрагентов.
        // Минимально заполняем ключевые поля; остальное пусть остаётся null.
        $buffer = [];

        for ($i = 1; $i <= $count; $i++) {
            $id = (string) Str::uuid();
            $name = 'Контрагент '.str_pad((string) $i, 6, '0', STR_PAD_LEFT);

            // В таблице есть уникальные ограничения на inn/ogrn, поэтому делаем их детерминированными.
            // Важно: базовый CounterpartySeeder использует ИНН вида 77********,
            // поэтому здесь используем префикс 99, чтобы исключить пересечения.
            $seed2 = $seed % 100;
            $inn = '99'.str_pad((string) (($seed2 * 1_000_000) + $i), 8, '0', STR_PAD_LEFT); // 10 digits
            $ogrn = '99'.str_pad((string) (($seed2 * 10_000_000_000) + $i), 11, '0', STR_PAD_LEFT); // 13 digits

            $buffer[] = [
                'id' => $id,
                'type' => 'legal_entity',
                'name' => $name,
                'short_name' => $name,
                'inn' => $inn,
                'kpp' => fake()->numerify('#########'),
                'ogrn' => $ogrn,
                'okved' => fake()->numerify('##.##'),
                'okpo' => fake()->numerify('########'),
                'legal_address_country' => 'RU',
                'legal_address_region' => fake()->city().'ская область',
                'legal_address_city' => fake()->city(),
                'legal_address_street' => fake()->streetName(),
                'legal_address_house' => fake()->buildingNumber(),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            if (count($buffer) >= $chunkSize) {
                DB::table('counterparties')->insertOrIgnore($buffer);
                $buffer = [];
            }
        }

        if (! empty($buffer)) {
            DB::table('counterparties')->insertOrIgnore($buffer);
        }
    }

    private function seedPropertyDefinitions(int $count, Carbon $now): void
    {
        $this->command?->info(" - Seeding {$count} property definitions...");

        $types = [
            PropertyTypeEnum::String,
            PropertyTypeEnum::Number,
            PropertyTypeEnum::Boolean,
            PropertyTypeEnum::Enum,
            PropertyTypeEnum::Multiselect,
            PropertyTypeEnum::Color,
            PropertyTypeEnum::Datetime,
        ];

        $buffer = [];

        for ($i = 1; $i <= $count; $i++) {
            $type = $types[($i - 1) % count($types)];
            $name = 'Свойство '.str_pad((string) $i, 3, '0', STR_PAD_LEFT);

            $enumValues = null;
            if ($type->requiresEnumValues()) {
                $values = array_slice(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Black', 'White', 'Red', 'Blue'], 0, fake()->numberBetween(3, 6));
                $enumValues = array_map(fn (string $v) => ['id' => (string) Str::uuid(), 'value' => $v], $values);
            }

            $buffer[] = [
                'id' => (string) Str::uuid(),
                'name' => json_encode(['ru' => $name, 'en' => $name], JSON_UNESCAPED_UNICODE),
                'code' => 'PROP-LD-'.$this->runKey.'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'type' => $type->value,
                'unit' => $type === PropertyTypeEnum::Number ? fake()->randomElement(['шт', 'кг', 'см', 'л']) : null,
                'is_filterable' => fake()->boolean(55),
                'is_required' => fake()->boolean(10),
                'use_for_catalog' => true,
                'use_for_offers' => fake()->boolean(30),
                'use_for_documents' => false,
                'enum_values' => $enumValues ? json_encode($enumValues, JSON_UNESCAPED_UNICODE) : null,
                'applies_to_all' => fake()->boolean(25),
                'category_ids' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('property_definitions')->insertOrIgnore($buffer);
    }

    /**
     * Основной генератор каталога с зависимостями.
     *
     * @param  array<int, array{id:string,currency:string|null}>  $priceTypes
     * @param  array<int, PropertyDefinition>  $propertyDefinitions
     * @param  array<int, string>  $warehouseIds
     */
    private function seedCatalog(
        int $productsCount,
        array $categoryIds,
        array $manufacturerIds,
        array $priceTypes,
        array $warehouseIds,
        string $defaultWarehouseId,
        array $propertyDefinitions,
        float $variantsRatio,
        int $variantsMin,
        int $variantsMax,
        int $priceTiers,
        int $stocksPerOffer,
        int $productPropertiesPerEntity,
        int $variantPropertiesPerEntity,
        int $chunkSize,
        Carbon $now
    ): void {
        $this->command?->info(" - Seeding {$productsCount} products (+ variants/offers/prices/stocks/images/properties)...");

        $productSeq = 1;
        $variantSeq = 1;
        $offerSeq = 1;
        $priceSeq = 1;
        $stockSeq = 1;
        $imageSeq = 1;
        $propertyValueSeq = 1;

        $currencyByPriceTypeId = [];
        foreach ($priceTypes as $pt) {
            $currencyByPriceTypeId[$pt['id']] = $pt['currency'] ?? CurrencyEnum::RUB->value;
        }

        $warehousePool = array_values($warehouseIds);

        while ($productSeq <= $productsCount) {
            $batchStart = $productSeq;
            $batchSize = min($chunkSize, $productsCount - $productSeq + 1);
            $batchEnd = $batchStart + $batchSize - 1;

            $products = [];
            $variants = [];
            $offers = [];
            $offerPrices = [];
            $stocks = [];
            $images = [];
            $propertyValues = [];

            $productIdsInBatch = [];
            $variantIdsInBatch = [];
            $offerIdsInBatch = [];

            for ($i = 0; $i < $batchSize; $i++, $productSeq++) {
                $productId = (string) Str::uuid();
                $productIdsInBatch[] = $productId;

                $name = 'Товар '.str_pad((string) $productSeq, 7, '0', STR_PAD_LEFT);
                $barcode = str_pad((string) $productSeq, 14, '0', STR_PAD_LEFT);

                $categoryId = $categoryIds[array_rand($categoryIds)];
                $manufacturerId = ! empty($manufacturerIds) && fake()->boolean(35) ? $manufacturerIds[array_rand($manufacturerIds)] : null;
                $brandOwnerId = $manufacturerId && fake()->boolean(30) ? $manufacturerIds[array_rand($manufacturerIds)] : null;

                $isActive = fake()->boolean(96);

                $products[] = [
                    'id' => $productId,
                    'external_id' => 'EXT-P-'.$this->runKey.'-'.str_pad((string) $productSeq, 9, '0', STR_PAD_LEFT),
                    'name' => $name,
                    'code' => 'PRD-LD-'.$this->runKey.'-'.str_pad((string) $productSeq, 8, '0', STR_PAD_LEFT),
                    'barcode' => $barcode,
                    'category_id' => $categoryId,
                    'description' => fake()->paragraph(3),
                    'short_description' => fake()->sentence(10),
                    'tax_rate' => fake()->boolean(85) ? 20.00 : 0.00,
                    'weight' => fake()->randomFloat(3, 0.01, 80),
                    'dimensions_length' => fake()->randomFloat(2, 1, 200),
                    'dimensions_width' => fake()->randomFloat(2, 1, 200),
                    'dimensions_height' => fake()->randomFloat(2, 1, 200),
                    'manufacturer_country' => 'RU',
                    'manufacturer_brand' => fake()->company(),
                    'manufacturer_brand_owner_id' => $brandOwnerId,
                    'manufacturer_id' => $manufacturerId,
                    'unit_code' => '796',
                    'unit_short_name' => 'шт',
                    'unit_full_name' => 'штука',
                    'unit_international' => 'PCE',
                    'is_active' => $isActive,
                    'seo_title' => "Купить {$name} - цена, характеристики",
                    'seo_description' => fake()->sentence(20),
                    'seo_keywords' => implode(', ', fake()->words(10)),
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];

                // Images: 1 main + 0..3 additional
                $mainUrl = fake()->imageUrl(1200, 1200, 'technics', true);
                $images[] = [
                    'id' => (string) Str::uuid(),
                    'product_id' => $productId,
                    'url' => $mainUrl,
                    'sort' => 1,
                    'alt' => $name,
                    'is_main' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $imageSeq++;

                $additionalCount = fake()->numberBetween(0, 3);
                for ($k = 0; $k < $additionalCount; $k++, $imageSeq++) {
                    $images[] = [
                        'id' => (string) Str::uuid(),
                        'product_id' => $productId,
                        'url' => fake()->imageUrl(1200, 1200, 'technics', true),
                        'sort' => 2 + $k,
                        'alt' => $name.' ('.($k + 1).')',
                        'is_main' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Product-level offer (variant_id = null) - always exactly one
                $offerId = (string) Str::uuid();
                $offerIdsInBatch[] = $offerId;
                $offers[] = [
                    'id' => $offerId,
                    'product_id' => $productId,
                    'variant_id' => null,
                    'deleted_at' => null,
                    'updated_at' => $now,
                ];
                $offerSeq++;

                // Prices for offer
                $this->appendOfferPrices(
                    offerPrices: $offerPrices,
                    offerId: $offerId,
                    priceTypes: $priceTypes,
                    currencyByPriceTypeId: $currencyByPriceTypeId,
                    baseAmount: $this->priceBaseAmount($productSeq),
                    priceTiers: $priceTiers,
                    now: $now
                );
                $priceSeq += count($priceTypes) * $priceTiers;

                // Stocks (per offer)
                if ($stocksPerOffer > 0) {
                    $this->appendStocks(
                        stocks: $stocks,
                        offerId: $offerId,
                        warehousePool: $warehousePool,
                        defaultWarehouseId: $defaultWarehouseId,
                        perOffer: $stocksPerOffer,
                        now: $now
                    );
                    $stockSeq += $stocksPerOffer;
                }

                // Product properties
                if ($productPropertiesPerEntity > 0 && ! empty($propertyDefinitions)) {
                    $this->appendPropertyValues(
                        propertyValues: $propertyValues,
                        entity: 'product',
                        entityId: $productId,
                        propertyDefinitions: $propertyDefinitions,
                        perEntity: $productPropertiesPerEntity,
                        seqStart: $propertyValueSeq,
                        now: $now
                    );
                    $propertyValueSeq += $productPropertiesPerEntity;
                }

                // Variants for a subset of products
                if ($variantsMax > 0 && fake()->randomFloat(4, 0, 1) < $variantsRatio) {
                    $countVariants = fake()->numberBetween($variantsMin, $variantsMax);
                    for ($v = 1; $v <= $countVariants; $v++, $variantSeq++) {
                        $variantId = (string) Str::uuid();
                        $variantIdsInBatch[] = $variantId;

                        $variantName = "{$name} / Вариант {$v}";
                        $variantBarcode = str_pad((string) (10_000_000 + $variantSeq), 14, '0', STR_PAD_LEFT);

                        $variants[] = [
                            'id' => $variantId,
                            'product_id' => $productId,
                            'external_id' => 'EXT-V-'.$this->runKey.'-'.str_pad((string) $variantSeq, 10, '0', STR_PAD_LEFT),
                            'name' => $variantName,
                            'code' => 'VAR-LD-'.$this->runKey.'-'.str_pad((string) $variantSeq, 9, '0', STR_PAD_LEFT),
                            'barcode' => $variantBarcode,
                            'is_active' => $isActive,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        // Offer for variant
                        $variantOfferId = (string) Str::uuid();
                        $offerIdsInBatch[] = $variantOfferId;
                        $offers[] = [
                            'id' => $variantOfferId,
                            'product_id' => $productId,
                            'variant_id' => $variantId,
                            'deleted_at' => null,
                            'updated_at' => $now,
                        ];
                        $offerSeq++;

                        // Prices for variant offer (small delta)
                        $this->appendOfferPrices(
                            offerPrices: $offerPrices,
                            offerId: $variantOfferId,
                            priceTypes: $priceTypes,
                            currencyByPriceTypeId: $currencyByPriceTypeId,
                            baseAmount: $this->priceBaseAmount($productSeq) * fake()->randomFloat(2, 0.95, 1.15),
                            priceTiers: $priceTiers,
                            now: $now
                        );

                        if ($stocksPerOffer > 0) {
                            $this->appendStocks(
                                stocks: $stocks,
                                offerId: $variantOfferId,
                                warehousePool: $warehousePool,
                                defaultWarehouseId: $defaultWarehouseId,
                                perOffer: $stocksPerOffer,
                                now: $now
                            );
                        }

                        if ($variantPropertiesPerEntity > 0 && ! empty($propertyDefinitions)) {
                            $this->appendPropertyValues(
                                propertyValues: $propertyValues,
                                entity: 'variant',
                                entityId: $variantId,
                                propertyDefinitions: $propertyDefinitions,
                                perEntity: $variantPropertiesPerEntity,
                                seqStart: $propertyValueSeq,
                                now: $now
                            );
                            $propertyValueSeq += $variantPropertiesPerEntity;
                        }
                    }
                }
            }

            try {
                DB::transaction(function () use ($products, $variants, $offers, $offerPrices, $stocks, $images, $propertyValues) {
                    if (! empty($products)) {
                        foreach (array_chunk($products, 2000) as $chunk) {
                            DB::table('products')->insert($chunk);
                        }
                    }
                    if (! empty($variants)) {
                        foreach (array_chunk($variants, 2000) as $chunk) {
                            DB::table('product_variants')->insert($chunk);
                        }
                    }
                    if (! empty($offers)) {
                        foreach (array_chunk($offers, 5000) as $chunk) {
                            DB::table('offers')->insert($chunk);
                        }
                    }
                    if (! empty($offerPrices)) {
                        // offer_prices имеет 20 полей, поэтому 2000 записей = 40000 параметров
                        // Уменьшаем до 1500 для безопасности
                        foreach (array_chunk($offerPrices, 1500) as $chunk) {
                            DB::table('offer_prices')->insert($chunk);
                        }
                    }
                    if (! empty($stocks)) {
                        foreach (array_chunk($stocks, 5000) as $chunk) {
                            DB::table('stocks')->insert($chunk);
                        }
                    }
                    if (! empty($images)) {
                        foreach (array_chunk($images, 5000) as $chunk) {
                            DB::table('product_images')->insert($chunk);
                        }
                    }
                    if (! empty($propertyValues)) {
                        // Уменьшенный размер чанка для PostgreSQL (лимит 65535 параметров)
                        // Каждая запись property_values имеет 8 полей, поэтому 3000 записей = 24000 параметров
                        foreach (array_chunk($propertyValues, 3000) as $chunk) {
                            DB::table('property_values')->insert($chunk);
                        }
                    }
                });
            } catch (QueryException $e) {
                throw new \RuntimeException(
                    "LoadTestDatabaseSeeder failed while inserting batch {$batchStart}..{$batchEnd}: {$e->getMessage()}",
                    previous: $e
                );
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $offerPrices
     * @param  array<int, array{id:string,currency:string|null}>  $priceTypes
     * @param  array<string, string>  $currencyByPriceTypeId
     */
    private function appendOfferPrices(
        array &$offerPrices,
        string $offerId,
        array $priceTypes,
        array $currencyByPriceTypeId,
        float $baseAmount,
        int $priceTiers,
        Carbon $now
    ): void {
        foreach ($priceTypes as $pt) {
            $ptId = $pt['id'];
            $currency = $currencyByPriceTypeId[$ptId] ?? CurrencyEnum::RUB->value;

            // Tier 1: min_quantity = 1
            $amount = round($baseAmount * fake()->randomFloat(2, 0.9, 1.2), 2);
            $discountPercent = fake()->boolean(20) ? fake()->randomElement([5.00, 10.00, 15.00]) : null;
            $discountAmount = $discountPercent ? round($amount * (1 - ($discountPercent / 100)), 2) : null;

            $offerPrices[] = [
                'id' => (string) Str::uuid(),
                'offer_id' => $offerId,
                'price_type_id' => $ptId,
                'price_amount' => $amount,
                'price_currency' => $currency,
                'price_with_discount_amount' => $discountAmount,
                'price_with_discount_currency' => $discountAmount ? $currency : null,
                'discount_percent' => $discountPercent,
                'min_quantity' => 1.000,
                'unit_code' => '796',
                'unit_short_name' => 'шт',
                'unit_full_name' => 'штука',
                'unit_international' => 'PCE',
                'valid_from' => null,
                'valid_to' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($priceTiers >= 2) {
                // Tier 2: min_quantity = 10 (опт/скидка на объем)
                $amount2 = round($amount * fake()->randomFloat(2, 0.82, 0.95), 2);
                $offerPrices[] = [
                    'id' => (string) Str::uuid(),
                    'offer_id' => $offerId,
                    'price_type_id' => $ptId,
                    'price_amount' => $amount2,
                    'price_currency' => $currency,
                    'price_with_discount_amount' => null,
                    'price_with_discount_currency' => null,
                    'discount_percent' => null,
                    'min_quantity' => 10.000,
                    'unit_code' => '796',
                    'unit_short_name' => 'шт',
                    'unit_full_name' => 'штука',
                    'unit_international' => 'PCE',
                    'valid_from' => null,
                    'valid_to' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $stocks
     * @param  array<int, string>  $warehousePool
     */
    private function appendStocks(
        array &$stocks,
        string $offerId,
        array $warehousePool,
        string $defaultWarehouseId,
        int $perOffer,
        Carbon $now
    ): void {
        $selected = [];
        if ($perOffer === 1) {
            $selected[] = $defaultWarehouseId;
        } else {
            $selected[] = $defaultWarehouseId;
            while (count($selected) < $perOffer && count($selected) < count($warehousePool)) {
                $candidate = $warehousePool[array_rand($warehousePool)];
                $selected[$candidate] = $candidate;
            }
            $selected = array_values(array_unique($selected));
        }

        foreach ($selected as $warehouseId) {
            $qty = fake()->boolean(20) ? 0.000 : fake()->randomFloat(3, 0, 500);
            $reserved = $qty > 0 && fake()->boolean(35) ? min($qty, fake()->randomFloat(3, 0, $qty)) : 0.000;

            $stocks[] = [
                'id' => (string) Str::uuid(),
                'offer_id' => $offerId,
                'warehouse_id' => $warehouseId,
                'quantity' => number_format($qty, 3, '.', ''),
                'quantity_reserved' => number_format($reserved, 3, '.', ''),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $propertyValues
     * @param  array<int, PropertyDefinition>  $propertyDefinitions
     */
    private function appendPropertyValues(
        array &$propertyValues,
        string $entity,
        string $entityId,
        array $propertyDefinitions,
        int $perEntity,
        int $seqStart,
        Carbon $now
    ): void {
        $count = min($perEntity, count($propertyDefinitions));
        if ($count <= 0) {
            return;
        }

        $picked = [];
        $maxTries = $count * 5;
        $tries = 0;
        while (count($picked) < $count && $tries++ < $maxTries) {
            $idx = array_rand($propertyDefinitions);
            $picked[$propertyDefinitions[$idx]->id] = $propertyDefinitions[$idx];
        }

        $seq = $seqStart;
        foreach ($picked as $property) {
            $rawType = $property->type;
            $type = $rawType instanceof PropertyTypeEnum
                ? $rawType
                : PropertyTypeEnum::from((string) $rawType);

            $valueString = null;
            $valueNumber = null;
            $valueBoolean = null;
            $valueJson = null;

            switch ($type) {
                case PropertyTypeEnum::String:
                    $valueString = fake()->sentence(3);
                    break;
                case PropertyTypeEnum::Number:
                    $valueNumber = fake()->randomFloat(4, 0, 9999);
                    break;
                case PropertyTypeEnum::Boolean:
                    $valueBoolean = fake()->boolean();
                    break;
                case PropertyTypeEnum::Color:
                    $valueString = sprintf('#%06X', fake()->numberBetween(0, 0xFFFFFF));
                    break;
                case PropertyTypeEnum::Datetime:
                    $valueString = fake()->dateTimeBetween('-2 years')->format(DATE_ATOM);
                    break;
                case PropertyTypeEnum::Enum:
                    $enum = is_string($property->enum_values) ? json_decode($property->enum_values, true) : $property->enum_values;
                    $valueString = ! empty($enum) ? ($enum[array_rand($enum)]['value'] ?? fake()->word()) : fake()->word();
                    break;
                case PropertyTypeEnum::Multiselect:
                    $enum = is_string($property->enum_values) ? json_decode($property->enum_values, true) : $property->enum_values;
                    if (! empty($enum)) {
                        $values = array_map(
                            fn ($e) => $e['value'] ?? null,
                            (array) $enum
                        );
                        $values = array_values(array_filter($values, fn ($v) => is_string($v) && $v !== ''));
                        $take = min(count($values), fake()->numberBetween(1, max(1, min(3, count($values)))));
                        shuffle($values);
                        $valueJson = json_encode(array_slice($values, 0, $take), JSON_UNESCAPED_UNICODE);
                    } else {
                        $valueJson = json_encode([fake()->word()], JSON_UNESCAPED_UNICODE);
                    }
                    break;
            }

            $propertyValues[] = [
                'id' => (string) Str::uuid(),
                'property_id' => $property->id,
                'product_id' => $entity === 'product' ? $entityId : null,
                'variant_id' => $entity === 'variant' ? $entityId : null,
                'value_string' => $valueString,
                'value_number' => $valueNumber,
                'value_boolean' => $valueBoolean,
                'value_json' => $valueJson,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $seq++;
        }
    }

    private function priceBaseAmount(int $seq): float
    {
        // Сегментация по "ценовым кластерам" — полезно для анализа планов запросов/агрегаций.
        $bucket = $seq % 10;

        return match (true) {
            $bucket <= 2 => fake()->randomFloat(2, 99, 999),
            $bucket <= 6 => fake()->randomFloat(2, 1000, 9999),
            default => fake()->randomFloat(2, 10000, 199999),
        };
    }
}
