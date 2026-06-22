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
                'external_id' => 'MSK-WH',
                'name' => 'Центральный склад (Москва)',
                'code' => 'WH-MSK',
                'address_country' => 'RU',
                'address_region' => 'Московская область',
                'address_city' => 'Москва',
                'address_street' => 'ул. Автомобильная',
                'address_house' => '15',
                'address_postal_code' => '101000',
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000102',
                'external_id' => 'SPB-WH',
                'name' => 'Северо-Западный склад (СПб)',
                'code' => 'WH-SPB',
                'address_country' => 'RU',
                'address_region' => 'Ленинградская область',
                'address_city' => 'Санкт-Петербург',
                'address_street' => 'пр. Автозаводской',
                'address_house' => '8',
                'address_postal_code' => '190000',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000103',
                'external_id' => 'UPL-WH',
                'name' => 'Уральский склад (Екатеринбург)',
                'code' => 'WH-UPL',
                'address_country' => 'RU',
                'address_region' => 'Свердловская область',
                'address_city' => 'Екатеринбург',
                'address_street' => 'ул. Детальная',
                'address_house' => '3',
                'address_postal_code' => '620000',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000104',
                'external_id' => 'SOUTH-WH',
                'name' => 'Южный склад (Краснодар)',
                'code' => 'WH-SOUTH',
                'address_country' => 'RU',
                'address_region' => 'Краснодарский край',
                'address_city' => 'Краснодар',
                'address_street' => 'ул. Запчастинская',
                'address_house' => '42',
                'address_postal_code' => '350000',
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
