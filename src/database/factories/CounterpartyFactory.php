<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Models\BankAccount;
use GeekCo\CommerceJson\Models\Contact;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\Representative;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Counterparty>
 */
class CounterpartyFactory extends CommerceJsonFactory
{
    protected $model = Counterparty::class;

    public function definition(): array
    {
        $isLegalEntity = fake()->boolean(70); // 70% юридических лиц
        $name = fake()->unique()->company();

        return [
            'id' => static::uuid(),
            'external_id' => static::externalId(),
            'type' => $isLegalEntity
                ? CounterpartyTypeEnum::LegalEntity->value
                : CounterpartyTypeEnum::Individual->value,
            'name' => $name,
            'short_name' => $name,
            'inn' => static::inn($isLegalEntity),
            'kpp' => $isLegalEntity ? static::kpp() : null,
            'ogrn' => static::ogrn($isLegalEntity),
            'okved' => fake()->numerify('##.##'),
            'okpo' => fake()->numerify('########'),
            'okopf' => $isLegalEntity ? fake()->numerify('####') : null,
            'okfs' => $isLegalEntity ? fake()->numerify('##') : null,
            'registration_date' => fake()->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d'),
            'legal_address_country' => 'RU',
            'legal_address_region' => fake()->city(),
            'legal_address_district' => null,
            'legal_address_city' => fake()->city(),
            'legal_address_street' => fake()->streetName(),
            'legal_address_house' => fake()->buildingNumber(),
            'legal_address_building' => null,
            'legal_address_apartment' => null,
            'legal_address_postal_code' => fake()->postcode(),
            'legal_address_full' => null,
            'actual_address_country' => 'RU',
            'actual_address_region' => fake()->city(),
            'actual_address_district' => null,
            'actual_address_city' => fake()->city(),
            'actual_address_street' => fake()->streetName(),
            'actual_address_house' => fake()->buildingNumber(),
            'actual_address_building' => null,
            'actual_address_apartment' => null,
            'actual_address_postal_code' => fake()->postcode(),
            'actual_address_full' => null,
            'price_type_id' => null,
            'credit_limit_amount' => fake()->randomFloat(2, 50000, 2000000),
            'credit_limit_currency' => CurrencyEnum::RUB->value,
            'credit_limit_remaining_amount' => fake()->randomFloat(2, 0, 1000000),
            'credit_limit_remaining_currency' => CurrencyEnum::RUB->value,
            'payment_deferral_days' => fake()->boolean(70) ? fake()->randomElement([7, 14, 30, 45, 60]) : null,
            'outstanding_debt_amount' => fake()->boolean(50) ? fake()->randomFloat(2, 0, 500000) : null,
            'outstanding_debt_currency' => CurrencyEnum::RUB->value,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ];
    }

    /**
     * Юридическое лицо
     */
    public function legalEntity(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CounterpartyTypeEnum::LegalEntity->value,
            'inn' => static::inn(true),
            'kpp' => static::kpp(),
            'ogrn' => static::ogrn(true),
        ]);
    }

    /**
     * Индивидуальный предприниматель
     */
    public function individualEntrepreneur(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CounterpartyTypeEnum::IndividualEntrepreneur->value,
            'inn' => static::inn(false),
            'kpp' => null,
            'ogrn' => static::ogrn(false),
        ]);
    }

    /**
     * Физическое лицо
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CounterpartyTypeEnum::Individual->value,
            'inn' => static::inn(false),
            'kpp' => null,
            'ogrn' => null,
        ]);
    }

    /**
     * Контрагент с кредитным лимитом
     */
    public function withCreditLimit(float $amount = 100000): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit_amount' => $amount,
            'credit_limit_currency' => CurrencyEnum::RUB->value,
        ]);
    }

    /**
     * Контрагент с полной кредитной информацией
     */
    public function withCreditInfo(float $limit = 100000, float $remaining = 50000, float $debt = 25000, int $deferralDays = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit_amount' => $limit,
            'credit_limit_remaining_amount' => $remaining,
            'credit_limit_remaining_currency' => CurrencyEnum::RUB->value,
            'payment_deferral_days' => $deferralDays,
            'outstanding_debt_amount' => $debt,
            'outstanding_debt_currency' => CurrencyEnum::RUB->value,
        ]);
    }

    /**
     * Активный контрагент
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Неактивный контрагент
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Удалённый контрагент
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }

    /**
     * Контрагент со связанными сущностями (контакты, представители, счета, атрибуты)
     */
    public function withRelations(): static
    {
        return $this->afterCreating(function (Counterparty $counterparty) {
            Contact::factory()->count(2)->forCounterparty($counterparty)->create();
            BankAccount::factory()->count(1)->forCounterparty($counterparty)->create();
            Representative::factory()->count(1)->forCounterparty($counterparty)->create();
            $counterparty->customAttributes()->createMany([
                ['key' => 'source', 'value_string' => 'factory', 'value_number' => null, 'value_boolean' => null, 'value_json' => null],
            ]);
        });
    }
}
