<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Seeders;

use GeekCo\CommerceJson\Models\Category;
use GeekCo\CommerceJson\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Тестовый сидер для товаров
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoriesByCode = Category::query()
            ->whereIn('code', ['CAT-PHONES', 'CAT-LAPTOPS', 'CAT-MENS', 'CAT-WOMENS'])
            ->pluck('id', 'code');

        $products = [
            [
                'name' => 'Смартфон Apple iPhone 15 Pro 256GB',
                'code' => 'PRD-IPHONE15PRO',
                'category_code' => 'CAT-PHONES',
                'price' => 129990,
                'weight' => 0.187,
            ],
            [
                'name' => 'Смартфон Samsung Galaxy S24 Ultra 512GB',
                'code' => 'PRD-GALAXYS24',
                'category_code' => 'CAT-PHONES',
                'price' => 119990,
                'weight' => 0.232,
            ],
            [
                'name' => 'Ноутбук MacBook Pro 16" M3 Max',
                'code' => 'PRD-MBPM3MAX',
                'category_code' => 'CAT-LAPTOPS',
                'price' => 399990,
                'weight' => 2.16,
            ],
            [
                'name' => 'Ноутбук ASUS ROG Zephyrus G16',
                'code' => 'PRD-ASUSG16',
                'category_code' => 'CAT-LAPTOPS',
                'price' => 249990,
                'weight' => 1.95,
            ],
            [
                'name' => 'Футболка мужская базовая',
                'code' => 'PRD-TSHIRT-M',
                'category_code' => 'CAT-MENS',
                'price' => 1990,
                'weight' => 0.2,
            ],
            [
                'name' => 'Джинсы мужские классические',
                'code' => 'PRD-JEANS-M',
                'category_code' => 'CAT-MENS',
                'price' => 4990,
                'weight' => 0.6,
            ],
            [
                'name' => 'Платье женское вечернее',
                'code' => 'PRD-DRESS-W',
                'category_code' => 'CAT-WOMENS',
                'price' => 8990,
                'weight' => 0.5,
            ],
            [
                'name' => 'Блузка женская офисная',
                'code' => 'PRD-BLOUSE-W',
                'category_code' => 'CAT-WOMENS',
                'price' => 3490,
                'weight' => 0.25,
            ],
        ];

        foreach ($products as $productData) {
            $categoryId = $categoriesByCode[$productData['category_code']] ?? null;
            if ($categoryId === null) {
                // fallback: если кто-то изменил CategorySeeder — создадим категорию фабрикой
                $categoryId = Category::factory()->create(['code' => $productData['category_code']])->id;
            }

            Product::factory()->create([
                'name' => $productData['name'],
                'code' => $productData['code'],
                'category_id' => $categoryId,
                'weight' => $productData['weight'],
            ]);
        }

        // Сгенерировать дополнительные случайные товары
        Product::factory(20)->create();

        $this->command->info('Products seeded successfully!');
    }
}
