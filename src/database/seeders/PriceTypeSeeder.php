<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Seeders;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Models\PriceType;
use Illuminate\Database\Seeder;

/**
 * Сидер для типов цен
 */
class PriceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $priceTypes = [
            [
                'id' => 'pt-retail-0000-0000-000000000001',
                'name' => 'Розничная цена',
                'currency' => CurrencyEnum::RUB->value,
                'description' => 'Стандартные розничные цены',
                'is_default' => true,
            ],
            [
                'id' => 'pt-wholesale-0000-0000-000000000002',
                'name' => 'Оптовая цена',
                'currency' => CurrencyEnum::RUB->value,
                'description' => 'Цены для оптовых покупателей (от 10 шт)',
                'is_default' => false,
            ],
            [
                'id' => 'pt-dealer-0000-0000-000000000003',
                'name' => 'Дилерская цена',
                'currency' => CurrencyEnum::RUB->value,
                'description' => 'Цены для дилеров и партнёров',
                'is_default' => false,
            ],
            [
                'id' => 'pt-vip-0000-0000-000000000004',
                'name' => 'VIP цена',
                'currency' => CurrencyEnum::RUB->value,
                'description' => 'Специальные цены для VIP клиентов',
                'is_default' => false,
            ],
        ];

        foreach ($priceTypes as $priceType) {
            PriceType::updateOrCreate(
                ['id' => $priceType['id']],
                $priceType
            );
        }

        $this->command->info('Price types seeded successfully!');
    }
}
