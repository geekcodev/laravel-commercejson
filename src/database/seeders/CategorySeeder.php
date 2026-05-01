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
            ['id' => '00000000-0000-0000-0000-000000001001'],
            [
                'parent_id' => null,
                'name' => 'Электроника',
                'code' => 'CAT-ELECTRONICS',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        $clothing = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001002'],
            [
                'parent_id' => null,
                'name' => 'Одежда',
                'code' => 'CAT-CLOTHING',
                'sort' => 2,
                'is_active' => true,
            ]
        );

        $home = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001003'],
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
            ['id' => '00000000-0000-0000-0000-000000001004'],
            [
                'parent_id' => $electronics->id,
                'name' => 'Смартфоны',
                'code' => 'CAT-PHONES',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        $laptops = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001005'],
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
            ['id' => '00000000-0000-0000-0000-000000001006'],
            [
                'parent_id' => $clothing->id,
                'name' => 'Мужская одежда',
                'code' => 'CAT-MENS',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        $womens = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001007'],
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
