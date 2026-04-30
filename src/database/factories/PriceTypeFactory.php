<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Models\PriceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceType>
 */
class PriceTypeFactory extends CommerceJsonFactory
{
    protected $model = PriceType::class;

    public function definition(): array
    {
        return [
            'id' => static::uuid(),
            'name' => fake()->unique()->words(2, true),
            'currency' => CurrencyEnum::RUB->value,
            'description' => fake()->sentence(),
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Тип цены по умолчанию
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'name' => 'Розничная цена',
        ]);
    }

    /**
     * Оптовый тип цены
     */
    public function wholesale(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Оптовая цена',
            'description' => 'Цены для оптовых покупателей',
        ]);
    }

    /**
     * Дилерский тип цены
     */
    public function dealer(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Дилерская цена',
            'description' => 'Цены для дилеров',
        ]);
    }

    /**
     * Тип цены с валютой
     */
    public function withCurrency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => $currency,
        ]);
    }
}
