<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Seeders;

use GeekCo\CommerceJson\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Сидер для категорий
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Корневые категории
        $electronics = Category::updateOrCreate(
            ['id' => 'cat-electronics-0000-000000000001'],
            [
                'parent_id' => null,
                'name' => 'Электроника',
                'code' => 'CAT-ELECTRONICS',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        $clothing = Category::updateOrCreate(
            ['id' => 'cat-clothing-0000-000000000002'],
            [
                'parent_id' => null,
                'name' => 'Одежда',
                'code' => 'CAT-CLOTHING',
                'sort' => 2,
                'is_active' => true,
            ]
        );

        $home = Category::updateOrCreate(
            ['id' => 'cat-home-0000-000000000003'],
            [
                'parent_id' => null,
                'name' => 'Для дома',
                'code' => 'CAT-HOME',
                'sort' => 3,
                'is_active' => true,
            ]
        );

        // Подкатегории электроники
        $phones = Category::updateOrCreate(
            ['id' => 'cat-phones-0000-000000000004'],
            [
                'parent_id' => $electronics->id,
                'name' => 'Смартфоны',
                'code' => 'CAT-PHONES',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        $laptops = Category::updateOrCreate(
            ['id' => 'cat-laptops-0000-000000000005'],
            [
                'parent_id' => $electronics->id,
                'name' => 'Ноутбуки',
                'code' => 'CAT-LAPTOPS',
                'sort' => 2,
                'is_active' => true,
            ]
        );

        // Подкатегории одежды
        $mens = Category::updateOrCreate(
            ['id' => 'cat-mens-0000-000000000006'],
            [
                'parent_id' => $clothing->id,
                'name' => 'Мужская одежда',
                'code' => 'CAT-MENS',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        $womens = Category::updateOrCreate(
            ['id' => 'cat-womens-0000-000000000007'],
            [
                'parent_id' => $clothing->id,
                'name' => 'Женская одежда',
                'code' => 'CAT-WOMENS',
                'sort' => 2,
                'is_active' => true,
            ]
        );

        $this->command->info('Categories seeded successfully!');
    }
}
