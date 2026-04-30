<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use GeekCo\CommerceJson\Models\Counterparty;
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
            'credit_limit_amount' => null,
            'credit_limit_currency' => 'RUB',
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
            'credit_limit_currency' => 'RUB',
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
}
