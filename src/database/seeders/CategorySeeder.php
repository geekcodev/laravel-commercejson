<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Seeders;

use GeekCo\CommerceJson\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Сидер для категорий автозапчастей
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Корневые категории
        $engine = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001001'],
            [
                'parent_id' => null,
                'name' => 'Двигатель и выхлопная система',
                'code' => 'CAT-ENGINE',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        $transmission = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001002'],
            [
                'parent_id' => null,
                'name' => 'Трансмиссия и подвеска',
                'code' => 'CAT-TRANSMISSION',
                'sort' => 2,
                'is_active' => true,
            ]
        );

        $brakes = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001003'],
            [
                'parent_id' => null,
                'name' => 'Тормозная система',
                'code' => 'CAT-BRAKES',
                'sort' => 3,
                'is_active' => true,
            ]
        );

        $filters = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001004'],
            [
                'parent_id' => null,
                'name' => 'Фильтры и расходники',
                'code' => 'CAT-FILTERS',
                'sort' => 4,
                'is_active' => true,
            ]
        );

        $electrics = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001005'],
            [
                'parent_id' => null,
                'name' => 'Электрика и электроника',
                'code' => 'CAT-ELECTRICS',
                'sort' => 5,
                'is_active' => true,
            ]
        );

        $body = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001006'],
            [
                'parent_id' => null,
                'name' => 'Кузов и оптика',
                'code' => 'CAT-BODY',
                'sort' => 6,
                'is_active' => true,
            ]
        );

        // Подкатегории двигателя
        $engineParts = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001007'],
            [
                'parent_id' => $engine->id,
                'name' => 'Детали двигателя',
                'code' => 'CAT-ENGINE-PARTS',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        $cooling = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001008'],
            [
                'parent_id' => $engine->id,
                'name' => 'Система охлаждения',
                'code' => 'CAT-COOLING',
                'sort' => 2,
                'is_active' => true,
            ]
        );

        $exhaust = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001009'],
            [
                'parent_id' => $engine->id,
                'name' => 'Выхлопная система',
                'code' => 'CAT-EXHAUST',
                'sort' => 3,
                'is_active' => true,
            ]
        );

        // Подкатегории трансмиссии и подвески
        $gearbox = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001010'],
            [
                'parent_id' => $transmission->id,
                'name' => 'Коробка передач и сцепление',
                'code' => 'CAT-GEARBOX',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        $suspension = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001011'],
            [
                'parent_id' => $transmission->id,
                'name' => 'Ходовая часть и подвеска',
                'code' => 'CAT-SUSPENSION',
                'sort' => 2,
                'is_active' => true,
            ]
        );

        // Подкатегории тормозной системы
        $brakeParts = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001012'],
            [
                'parent_id' => $brakes->id,
                'name' => 'Колодки и диски',
                'code' => 'CAT-BRAKE-PARTS',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        $brakeHydraulics = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001013'],
            [
                'parent_id' => $brakes->id,
                'name' => 'Гидравлика и привод',
                'code' => 'CAT-BRAKE-HYDRAULICS',
                'sort' => 2,
                'is_active' => true,
            ]
        );

        // Подкатегории фильтров
        $oilFilters = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001014'],
            [
                'parent_id' => $filters->id,
                'name' => 'Масляные фильтры',
                'code' => 'CAT-OIL-FILTERS',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        $airFilters = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001015'],
            [
                'parent_id' => $filters->id,
                'name' => 'Воздушные фильтры',
                'code' => 'CAT-AIR-FILTERS',
                'sort' => 2,
                'is_active' => true,
            ]
        );

        $fuelFilters = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001016'],
            [
                'parent_id' => $filters->id,
                'name' => 'Топливные фильтры',
                'code' => 'CAT-FUEL-FILTERS',
                'sort' => 3,
                'is_active' => true,
            ]
        );

        // Подкатегории электрики
        $starterGenerator = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001017'],
            [
                'parent_id' => $electrics->id,
                'name' => 'Генератор и стартер',
                'code' => 'CAT-STARTER-GEN',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        $sensors = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001018'],
            [
                'parent_id' => $electrics->id,
                'name' => 'Датчики и сенсоры',
                'code' => 'CAT-SENSORS',
                'sort' => 2,
                'is_active' => true,
            ]
        );

        // Подкатегории кузова
        $lights = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001019'],
            [
                'parent_id' => $body->id,
                'name' => 'Освещение и оптика',
                'code' => 'CAT-LIGHTS',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        $bodyParts = Category::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000001020'],
            [
                'parent_id' => $body->id,
                'name' => 'Кузовные детали',
                'code' => 'CAT-BODY-PARTS',
                'sort' => 2,
                'is_active' => true,
            ]
        );

        $this->command->info('Categories seeded successfully!');
    }
}
