<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Seeders;

use GeekCo\CommerceJson\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Тестовый сидер для товаров
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'cat-phones-0000-000000000004',
            'cat-laptops-0000-000000000005',
            'cat-mens-0000-000000000006',
            'cat-womens-0000-000000000007',
        ];

        $products = [
            [
                'name' => 'Смартфон Apple iPhone 15 Pro 256GB',
                'code' => 'PRD-IPHONE15PRO',
                'category_id' => $categories[0],
                'price' => 129990,
                'weight' => 0.187,
            ],
            [
                'name' => 'Смартфон Samsung Galaxy S24 Ultra 512GB',
                'code' => 'PRD-GALAXYS24',
                'category_id' => $categories[0],
                'price' => 119990,
                'weight' => 0.232,
            ],
            [
                'name' => 'Ноутбук MacBook Pro 16" M3 Max',
                'code' => 'PRD-MBPM3MAX',
                'category_id' => $categories[1],
                'price' => 399990,
                'weight' => 2.16,
            ],
            [
                'name' => 'Ноутбук ASUS ROG Zephyrus G16',
                'code' => 'PRD-ASUSG16',
                'category_id' => $categories[1],
                'price' => 249990,
                'weight' => 1.95,
            ],
            [
                'name' => 'Футболка мужская базовая',
                'code' => 'PRD-TSHIRT-M',
                'category_id' => $categories[2],
                'price' => 1990,
                'weight' => 0.2,
            ],
            [
                'name' => 'Джинсы мужские классические',
                'code' => 'PRD-JEANS-M',
                'category_id' => $categories[2],
                'price' => 4990,
                'weight' => 0.6,
            ],
            [
                'name' => 'Платье женское вечернее',
                'code' => 'PRD-DRESS-W',
                'category_id' => $categories[3],
                'price' => 8990,
                'weight' => 0.5,
            ],
            [
                'name' => 'Блузка женская офисная',
                'code' => 'PRD-BLOUSE-W',
                'category_id' => $categories[3],
                'price' => 3490,
                'weight' => 0.25,
            ],
        ];

        foreach ($products as $productData) {
            Product::factory()->create([
                'name' => $productData['name'],
                'code' => $productData['code'],
                'category_id' => $productData['category_id'],
                'weight' => $productData['weight'],
            ]);
        }

        // Сгенерировать дополнительные случайные товары
        Product::factory(20)->create();

        $this->command->info('Products seeded successfully!');
    }
}
