<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Seeders;

use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use GeekCo\CommerceJson\Models\Counterparty;
use Illuminate\Database\Seeder;

/**
 * Сидер для контрагентов — поставщиков автозапчастей
 */
class CounterpartySeeder extends Seeder
{
    public function run(): void
    {
        $counterparties = [
            // Юридические лица — производители и дистрибьюторы
            [
                'id' => '00000000-0000-0000-0000-000000002001',
                'type' => CounterpartyTypeEnum::LegalEntity->value,
                'name' => 'ООО "АвтоЗапчасть Трейд"',
                'short_name' => 'ООО "АЗТ"',
                'inn' => '7701123456',
                'kpp' => '770101001',
                'ogrn' => '1027700123456',
                'okved' => '45.31',
                'okpo' => '11223344',
                'legal_address_city' => 'Москва',
                'legal_address_street' => 'ул. Шоссейная',
                'legal_address_house' => '25',
                'is_active' => true,
            ],
            [
                'id' => '00000000-0000-0000-0000-000000002002',
                'type' => CounterpartyTypeEnum::LegalEntity->value,
                'name' => 'АО "Мотор-Деталь"',
                'short_name' => 'АО "МД"',
                'inn' => '7702234567',
                'kpp' => '770201001',
                'ogrn' => '1027700234567',
                'okved' => '29.32',
                'okpo' => '22334455',
                'legal_address_city' => 'Нижний Новгород',
                'legal_address_street' => 'ул. Заводская',
                'legal_address_house' => '5',
                'is_active' => true,
            ],

            // ИП
            [
                'id' => '00000000-0000-0000-0000-000000002003',
                'type' => CounterpartyTypeEnum::IndividualEntrepreneur->value,
                'name' => 'ИП Кузнецов Андрей Владимирович',
                'short_name' => 'ИП Кузнецов А.В.',
                'inn' => '770123456789',
                'kpp' => null,
                'ogrn' => '304770123456789',
                'okved' => '45.32',
                'okpo' => null,
                'legal_address_city' => 'Санкт-Петербург',
                'legal_address_street' => 'ул. Парковая',
                'legal_address_house' => '12',
                'is_active' => true,
            ],

            // Физические лица
            [
                'id' => '00000000-0000-0000-0000-000000002004',
                'type' => CounterpartyTypeEnum::Individual->value,
                'name' => 'Соколов Дмитрий Николаевич',
                'short_name' => 'Соколов Д.Н.',
                'inn' => '770234567890',
                'kpp' => null,
                'ogrn' => null,
                'okved' => null,
                'okpo' => null,
                'legal_address_city' => 'Екатеринбург',
                'legal_address_street' => 'ул. Уральская',
                'legal_address_house' => '8',
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
