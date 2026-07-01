<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Seeders;

use GeekCo\CommerceJson\Enums\ContactTypeEnum;
use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Models\BankAccount;
use GeekCo\CommerceJson\Models\Contact;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\CustomAttribute;
use GeekCo\CommerceJson\Models\Representative;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Сидер для контрагентов — поставщиков автозапчастей
 */
class CounterpartySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

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
                'credit_limit_amount' => 1000000.00,
                'credit_limit_currency' => CurrencyEnum::RUB->value,
                'credit_limit_remaining_amount' => 750000.00,
                'credit_limit_remaining_currency' => CurrencyEnum::RUB->value,
                'payment_deferral_days' => 30,
                'outstanding_debt_amount' => 150000.00,
                'outstanding_debt_currency' => CurrencyEnum::RUB->value,
                'contacts' => [
                    ['type' => ContactTypeEnum::Email->value, 'value' => 'info@azt.ru'],
                    ['type' => ContactTypeEnum::Phone->value, 'value' => '+7 (495) 123-45-67'],
                ],
                'bank_accounts' => [
                    ['bik' => '044525225', 'account' => '40702810700010000001', 'bank_name' => 'ПАО Сбербанк', 'is_default' => true],
                ],
                'representatives' => [
                    ['name' => 'Сергеев Петр Алексеевич', 'relation' => 'CEO', 'phone' => '+7 (495) 123-45-68', 'email' => 'p.sergeev@azt.ru'],
                ],
                'custom_attributes' => [
                    ['key' => 'source', 'value_string' => 'erp', 'value_number' => null, 'value_boolean' => null, 'value_json' => null],
                ],
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
                'credit_limit_amount' => 2000000.00,
                'credit_limit_currency' => CurrencyEnum::RUB->value,
                'credit_limit_remaining_amount' => 1200000.00,
                'credit_limit_remaining_currency' => CurrencyEnum::RUB->value,
                'payment_deferral_days' => 45,
                'outstanding_debt_amount' => 500000.00,
                'outstanding_debt_currency' => CurrencyEnum::RUB->value,
                'contacts' => [
                    ['type' => ContactTypeEnum::Email->value, 'value' => 'sales@motordetal.ru'],
                    ['type' => ContactTypeEnum::Phone->value, 'value' => '+7 (831) 234-56-78'],
                ],
                'bank_accounts' => [
                    ['bik' => '042202603', 'account' => '40702810900020000002', 'bank_name' => 'Банк ВТБ', 'is_default' => true],
                ],
                'representatives' => [
                    ['name' => 'Иванова Мария Сергеевна', 'relation' => 'Sales Manager', 'phone' => '+7 (831) 234-56-79', 'email' => 'm.ivanova@motordetal.ru'],
                ],
                'custom_attributes' => [
                    ['key' => 'rating', 'value_string' => null, 'value_number' => 5, 'value_boolean' => null, 'value_json' => null],
                ],
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
                'credit_limit_amount' => 300000.00,
                'credit_limit_currency' => CurrencyEnum::RUB->value,
                'credit_limit_remaining_amount' => 200000.00,
                'credit_limit_remaining_currency' => CurrencyEnum::RUB->value,
                'payment_deferral_days' => null,
                'outstanding_debt_amount' => null,
                'outstanding_debt_currency' => CurrencyEnum::RUB->value,
                'contacts' => [
                    ['type' => ContactTypeEnum::Email->value, 'value' => 'kuznetsov@example.com'],
                    ['type' => ContactTypeEnum::Mobile->value, 'value' => '+7 (921) 345-67-89'],
                ],
                'bank_accounts' => [],
                'representatives' => [],
                'custom_attributes' => [],
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
                'credit_limit_amount' => null,
                'credit_limit_currency' => CurrencyEnum::RUB->value,
                'credit_limit_remaining_amount' => null,
                'credit_limit_remaining_currency' => CurrencyEnum::RUB->value,
                'payment_deferral_days' => null,
                'outstanding_debt_amount' => null,
                'outstanding_debt_currency' => CurrencyEnum::RUB->value,
                'contacts' => [
                    ['type' => ContactTypeEnum::Email->value, 'value' => 'd.sokolov@example.com'],
                    ['type' => ContactTypeEnum::Phone->value, 'value' => '+7 (343) 456-78-90'],
                ],
                'bank_accounts' => [],
                'representatives' => [],
                'custom_attributes' => [
                    ['key' => 'is_vip', 'value_string' => null, 'value_number' => null, 'value_boolean' => true, 'value_json' => null],
                ],
            ],
        ];

        foreach ($counterparties as $item) {
            $contactsData = $item['contacts'];
            $bankAccountsData = $item['bank_accounts'];
            $representativesData = $item['representatives'];
            $customAttributesData = $item['custom_attributes'];

            $counterpartyData = collect($item)->except(['contacts', 'bank_accounts', 'representatives', 'custom_attributes'])->toArray();

            $counterpartyData['created_at'] = $now;
            $counterpartyData['updated_at'] = $now;

            $counterparty = Counterparty::updateOrCreate(
                ['id' => $counterpartyData['id']],
                $counterpartyData
            );

            foreach ($contactsData as $contact) {
                Contact::create([
                    'id' => (string) Str::uuid(),
                    'counterparty_id' => $counterparty->id,
                    'type' => $contact['type'],
                    'value' => $contact['value'],
                    'comment' => $contact['comment'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($bankAccountsData as $ba) {
                BankAccount::create([
                    'id' => (string) Str::uuid(),
                    'counterparty_id' => $counterparty->id,
                    'bik' => $ba['bik'],
                    'account' => $ba['account'],
                    'bank_name' => $ba['bank_name'] ?? null,
                    'corr_account' => $ba['corr_account'] ?? null,
                    'swift' => $ba['swift'] ?? null,
                    'is_default' => $ba['is_default'] ?? false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($representativesData as $rep) {
                Representative::create([
                    'id' => (string) Str::uuid(),
                    'counterparty_id' => $counterparty->id,
                    'name' => $rep['name'],
                    'relation' => $rep['relation'],
                    'phone' => $rep['phone'] ?? null,
                    'email' => $rep['email'] ?? null,
                    'position' => $rep['position'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($customAttributesData as $attr) {
                CustomAttribute::create([
                    'id' => (string) Str::uuid(),
                    'attributable_type' => $counterparty->getMorphClass(),
                    'attributable_id' => $counterparty->id,
                    'key' => $attr['key'],
                    'value_string' => $attr['value_string'] ?? null,
                    'value_number' => $attr['value_number'] ?? null,
                    'value_boolean' => $attr['value_boolean'] ?? null,
                    'value_json' => $attr['value_json'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command->info('Counterparties seeded successfully!');
    }
}
