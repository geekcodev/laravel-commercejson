<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Главный сидер для CommerceJSON package
 *
 * Запускает все сидеры в правильном порядке (с учётом foreign keys)
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting CommerceJSON database seeding...');

        // Load-test profile (high-volume dataset)
        // Enable with: COMMERCEJSON_SEED_PROFILE=load (read via config)
        if ((string) config('commercejson.seeding.profile') === 'load') {
            $this->call(LoadTestDatabaseSeeder::class);
            $this->command->info('✅ CommerceJSON load-test seeding completed!');

            return;
        }

        // 1. Справочники (без foreign keys)
        $this->call([
            PriceTypeSeeder::class,
            WarehouseSeeder::class,
        ]);

        // 2. Категории (для товаров)
        $this->call(CategorySeeder::class);

        // 3. Контрагенты (для заказов и производителей)
        $this->call(CounterpartySeeder::class);

        // 4. Товары (зависят от категорий)
        $this->call(ProductSeeder::class);

        // 5. Остальные сущности
        // $this->call(OrderSeeder::class);

        $this->command->info('✅ CommerceJSON database seeding completed!');
    }
}
