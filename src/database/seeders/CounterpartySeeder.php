<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Seeders;

use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use GeekCo\CommerceJson\Models\Counterparty;
use Illuminate\Database\Seeder;

/**
 * Сидер для контрагентов
 */
class CounterpartySeeder extends Seeder
{
    public function run(): void
    {
        $counterparties = [
            // Юридические лица
            [
                'id' => '00000000-0000-0000-0000-000000002001',
                'type' => CounterpartyTypeEnum::LegalEntity->value,
                'name' => 'ООО "Торговый Дом"',
                'short_name' => 'ООО "ТД"',
                'inn' => '7701234567',
                'kpp' => '770101001',
                'ogrn' => '1027700123456',
                'okved' => '46.90',
                'okpo' => '12345678',
                'legal_address_city' => 'Москва',
                'legal_address_street' => 'ул. Тверская',
                'legal_address_house' => '1',
                'is_active' => true,
            ],
            [
                'id' => '00000000-0000-0000-0000-000000002002',
                'type' => CounterpartyTypeEnum::LegalEntity->value,
                'name' => 'АО "Производственная Компания"',
                'short_name' => 'АО "ПК"',
                'inn' => '7702345678',
                'kpp' => '770201001',
                'ogrn' => '1027700234567',
                'okved' => '25.62',
                'okpo' => '23456789',
                'legal_address_city' => 'Москва',
                'legal_address_street' => 'ул. Ленина',
                'legal_address_house' => '10',
                'is_active' => true,
            ],

            // ИП
            [
                'id' => '00000000-0000-0000-0000-000000002003',
                'type' => CounterpartyTypeEnum::IndividualEntrepreneur->value,
                'name' => 'Иванов Иван Иванович',
                'short_name' => 'ИП Иванов И.И.',
                'inn' => '770123456789',
                'kpp' => null,
                'ogrn' => '304770123456789',
                'okved' => '47.91',
                'okpo' => null,
                'legal_address_city' => 'Санкт-Петербург',
                'legal_address_street' => 'Невский проспект',
                'legal_address_house' => '50',
                'is_active' => true,
            ],

            // Физические лица
            [
                'id' => '00000000-0000-0000-0000-000000002004',
                'type' => CounterpartyTypeEnum::Individual->value,
                'name' => 'Петров Пётр Петрович',
                'short_name' => 'Петров П.П.',
                'inn' => '770234567890',
                'kpp' => null,
                'ogrn' => null,
                'okved' => null,
                'okpo' => null,
                'legal_address_city' => 'Екатеринбург',
                'legal_address_street' => 'ул. Мира',
                'legal_address_house' => '25',
                'is_active' => true,
            ],
        ];

        foreach ($counterparties as $counterparty) {
            Counterparty::updateOrCreate(
                ['id' => $counterparty['id']],
                $counterparty
            );
        }

        $this->command->info('Counterparties seeded successfully!');
    }
}
