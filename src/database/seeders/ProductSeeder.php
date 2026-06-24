<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Seeders;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Models\Category;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\Offer;
use GeekCo\CommerceJson\Models\OfferPrice;
use GeekCo\CommerceJson\Models\PriceType;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Models\Stock;
use GeekCo\CommerceJson\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Тестовый сидер для товаров
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoriesByCode = Category::query()
            ->whereIn('code', [
                'CAT-ENGINE-PARTS', 'CAT-COOLING', 'CAT-EXHAUST',
                'CAT-GEARBOX', 'CAT-SUSPENSION',
                'CAT-BRAKE-PARTS', 'CAT-BRAKE-HYDRAULICS',
                'CAT-OIL-FILTERS', 'CAT-AIR-FILTERS', 'CAT-FUEL-FILTERS',
                'CAT-STARTER-GEN', 'CAT-SENSORS',
                'CAT-LIGHTS', 'CAT-BODY-PARTS',
            ])
            ->pluck('id', 'code');

        $priceTypes = PriceType::query()->orderBy('is_default', 'desc')->get();
        $warehouses = Warehouse::query()->get();
        $manufacturerIds = Counterparty::query()->pluck('id')->all();

        $products = [
            [
                'name' => 'Масло моторное синтетическое 5W-30 4л',
                'code' => 'PRD-ENGINE-OIL-5W30',
                'category_code' => 'CAT-ENGINE-PARTS',
                'weight' => 3.5,
                'price_retail' => 3200,
                'price_wholesale' => 2700,
                'price_dealer' => 2450,
                'price_vip' => 2300,
                'stock_qty' => [150, 80, 45, 60],
            ],
            [
                'name' => 'Колодки тормозные передние комплект',
                'code' => 'PRD-BRAKE-PADS-FRONT',
                'category_code' => 'CAT-BRAKE-PARTS',
                'weight' => 1.8,
                'price_retail' => 4500,
                'price_wholesale' => 3800,
                'price_dealer' => 3400,
                'price_vip' => 3100,
                'stock_qty' => [200, 120, 90, 75],
            ],
            [
                'name' => 'Фильтр воздушный двигателя',
                'code' => 'PRD-AIR-FILTER',
                'category_code' => 'CAT-AIR-FILTERS',
                'weight' => 0.3,
                'price_retail' => 850,
                'price_wholesale' => 650,
                'price_dealer' => 550,
                'price_vip' => 500,
                'stock_qty' => [500, 300, 200, 180],
            ],
            [
                'name' => 'Свеча зажигания NGK (4 шт)',
                'code' => 'PRD-SPARK-PLUG-NGK',
                'category_code' => 'CAT-ENGINE-PARTS',
                'weight' => 0.2,
                'price_retail' => 2400,
                'price_wholesale' => 1950,
                'price_dealer' => 1700,
                'price_vip' => 1550,
                'stock_qty' => [350, 200, 140, 110],
            ],
            [
                'name' => 'Ремень ГРМ комплект с роликами',
                'code' => 'PRD-TIMING-BELT-KIT',
                'category_code' => 'CAT-ENGINE-PARTS',
                'weight' => 1.2,
                'price_retail' => 5800,
                'price_wholesale' => 4900,
                'price_dealer' => 4400,
                'price_vip' => 4000,
                'stock_qty' => [80, 40, 25, 30],
            ],
            [
                'name' => 'Фильтр масляный',
                'code' => 'PRD-OIL-FILTER',
                'category_code' => 'CAT-OIL-FILTERS',
                'weight' => 0.25,
                'price_retail' => 650,
                'price_wholesale' => 500,
                'price_dealer' => 420,
                'price_vip' => 380,
                'stock_qty' => [600, 400, 300, 250],
            ],
            [
                'name' => 'Амортизатор передний правый',
                'code' => 'PRD-SHOCK-ABSORBER-FR',
                'category_code' => 'CAT-SUSPENSION',
                'weight' => 3.0,
                'price_retail' => 7500,
                'price_wholesale' => 6200,
                'price_dealer' => 5600,
                'price_vip' => 5200,
                'stock_qty' => [60, 30, 20, 25],
            ],
            [
                'name' => 'Диск тормозной перфорированный передний',
                'code' => 'PRD-BRAKE-DISC-FRONT',
                'category_code' => 'CAT-BRAKE-PARTS',
                'weight' => 8.5,
                'price_retail' => 6200,
                'price_wholesale' => 5200,
                'price_dealer' => 4700,
                'price_vip' => 4400,
                'stock_qty' => [120, 60, 40, 35],
            ],
        ];

        foreach ($products as $productData) {
            $categoryId = $categoriesByCode[$productData['category_code']] ?? null;
            if ($categoryId === null) {
                $categoryId = Category::factory()->create(['code' => $productData['category_code']])->id;
            }

            $product = Product::factory()->create([
                'name' => $productData['name'],
                'code' => $productData['code'],
                'category_id' => $categoryId,
                'weight' => $productData['weight'],
                'manufacturer_id' => ! empty($manufacturerIds) ? $manufacturerIds[array_rand($manufacturerIds)] : null,
            ]);

            $offer = Offer::factory()->forProduct($product)->create();

            foreach ($priceTypes as $index => $priceType) {
                $amount = match ($index) {
                    0 => $productData['price_retail'],
                    1 => $productData['price_wholesale'],
                    2 => $productData['price_dealer'],
                    3 => $productData['price_vip'],
                    default => $productData['price_retail'],
                };

                $isDiscount = $index >= 2;
                $discountPercent = $isDiscount ? round((1 - $amount / $productData['price_retail']) * 100, 2) : null;

                OfferPrice::factory()
                    ->forOffer($offer)
                    ->forPriceType($priceType)
                    ->create([
                        'price_amount' => $amount,
                        'price_with_discount_amount' => $isDiscount ? $amount : null,
                        'price_with_discount_currency' => $isDiscount ? CurrencyEnum::RUB->value : null,
                        'discount_percent' => $discountPercent,
                        'min_quantity' => $index >= 2 ? ($index === 3 ? 20 : 10) : 1,
                    ]);
            }

            foreach ($warehouses as $whIndex => $warehouse) {
                $qty = $productData['stock_qty'][$whIndex] ?? fake()->randomFloat(3, 0, 200);

                Stock::factory()
                    ->forOffer($offer)
                    ->forWarehouse($warehouse)
                    ->create([
                        'quantity' => $qty,
                        'quantity_reserved' => (int) ($qty * 0.1),
                    ]);
            }
        }

        // Сгенерировать дополнительные случайные автозапчасти
        $randomProducts = Product::factory(20)->create();
        if (! empty($manufacturerIds)) {
            foreach ($randomProducts as $rp) {
                $rp->update(['manufacturer_id' => $manufacturerIds[array_rand($manufacturerIds)]]);
            }
            $randomProducts->load('manufacturer');
        }
        foreach ($randomProducts as $product) {
            $offer = Offer::factory()->forProduct($product)->create();

            foreach ($priceTypes as $index => $priceType) {
                $basePrice = fake()->randomFloat(2, 300, 15000);
                $discounts = [0, 0.85, 0.75, 0.65];

                $amount = round($basePrice * ($discounts[$index] ?? 1), 2);
                $isDiscount = $index >= 2;
                $discountPercent = $isDiscount ? round((1 - $discounts[$index]) * 100, 2) : null;

                OfferPrice::factory()
                    ->forOffer($offer)
                    ->forPriceType($priceType)
                    ->create([
                        'price_amount' => $amount,
                        'price_with_discount_amount' => $isDiscount ? $amount : null,
                        'price_with_discount_currency' => $isDiscount ? CurrencyEnum::RUB->value : null,
                        'discount_percent' => $discountPercent,
                        'min_quantity' => $index >= 2 ? ($index === 3 ? 20 : 10) : 1,
                    ]);
            }

            foreach ($warehouses as $warehouse) {
                Stock::factory()
                    ->forOffer($offer)
                    ->forWarehouse($warehouse)
                    ->create([
                        'quantity' => fake()->randomFloat(3, 0, 200),
                        'quantity_reserved' => fake()->randomFloat(3, 0, 50),
                    ]);
            }
        }

        // Аналоги: каждому товару 5-7 аналогов от разных производителей
        $this->assignAnaloguesFromDifferentManufacturers();

        $this->command->info('Auto parts seeded successfully!');
    }

    private function assignAnaloguesFromDifferentManufacturers(): void
    {
        $allProductIds = Product::query()->pluck('id')->all();

        if (empty($allProductIds)) {
            return;
        }

        $productRows = DB::table('products')
            ->whereIn('id', $allProductIds)
            ->select('id', 'manufacturer_id')
            ->get();

        $productsByManufacturer = [];
        $noManufacturerIds = [];

        foreach ($productRows as $row) {
            if ($row->manufacturer_id !== null) {
                $productsByManufacturer[$row->manufacturer_id][] = $row->id;
            } else {
                $noManufacturerIds[] = $row->id;
            }
        }

        $allManufacturerIds = array_keys($productsByManufacturer);

        $candidatePoolByManufacturer = [];
        foreach ($allManufacturerIds as $mid) {
            $pool = $noManufacturerIds;
            foreach ($allManufacturerIds as $otherMid) {
                if ($otherMid !== $mid) {
                    array_push($pool, ...$productsByManufacturer[$otherMid]);
                }
            }
            $candidatePoolByManufacturer[$mid] = $pool;
        }

        $candidatePoolNoManufacturer = $noManufacturerIds;
        foreach ($allManufacturerIds as $mid) {
            array_push($candidatePoolNoManufacturer, ...$productsByManufacturer[$mid]);
        }

        $analogueBuffer = [];
        $now = now();

        foreach ($allProductIds as $productId) {
            $productManufacturerId = null;
            foreach ($productsByManufacturer as $mid => $ids) {
                if (in_array($productId, $ids, true)) {
                    $productManufacturerId = $mid;
                    break;
                }
            }

            $pool = $productManufacturerId !== null
                ? ($candidatePoolByManufacturer[$productManufacturerId] ?? [])
                : $candidatePoolNoManufacturer;

            $candidates = array_values(array_diff($pool, [$productId]));
            if (count($candidates) < 5) {
                $candidates = array_values(array_diff($allProductIds, [$productId]));
            }
            if (empty($candidates)) {
                continue;
            }

            $count = fake()->numberBetween(5, 7);
            $selected = fake()->randomElements($candidates, min($count, count($candidates)));

            foreach ($selected as $analogueId) {
                $analogueBuffer[] = [
                    'product_id' => $productId,
                    'analogue_id' => $analogueId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($analogueBuffer)) {
            foreach (array_chunk($analogueBuffer, 2000) as $chunk) {
                DB::table('product_analogues')->insert($chunk);
            }
        }
    }
}
