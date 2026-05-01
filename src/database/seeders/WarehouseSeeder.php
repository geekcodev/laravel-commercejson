<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Seeders;

use GeekCo\CommerceJson\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Сидер для складов
 */
class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            [
                'id' => '00000000-0000-0000-0000-000000000101',
                'external_id' => 'MAIN-WH',
                'name' => 'Основной склад',
                'code' => 'WH-MAIN',
                'address_country' => 'RU',
                'address_region' => 'Московская область',
                'address_city' => 'Москва',
                'address_street' => 'ул. Складская',
                'address_house' => '1',
                'address_postal_code' => '101000',
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000102',
                'external_id' => 'SPB-WH',
                'name' => 'Санкт-Петербургский склад',
                'code' => 'WH-SPB',
                'address_country' => 'RU',
                'address_region' => 'Ленинградская область',
                'address_city' => 'Санкт-Петербург',
                'address_street' => 'ул. Портовая',
                'address_house' => '15',
                'address_postal_code' => '190000',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000103',
                'external_id' => 'EKB-WH',
                'name' => 'Екатеринбургский склад',
                'code' => 'WH-EKB',
                'address_country' => 'RU',
                'address_region' => 'Свердловская область',
                'address_city' => 'Екатеринбург',
                'address_street' => 'ул. Логистическая',
                'address_house' => '7',
                'address_postal_code' => '620000',
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(
                ['id' => $warehouse['id']],
                $warehouse
            );
        }

        $this->command->info('Warehouses seeded successfully!');
    }
}
