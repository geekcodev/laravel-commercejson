<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends CommerceJsonFactory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        $city = fake()->city();

        return [
            'id' => static::uuid(),
            'external_id' => static::externalId(),
            'name' => fake()->unique()->company().' - Склад',
            'code' => 'WH-'.Str::upper(Str::random(6)),
            'address_country' => 'RU',
            'address_region' => $city.'ская область',
            'address_district' => null,
            'address_city' => $city,
            'address_street' => fake()->streetName(),
            'address_house' => fake()->buildingNumber(),
            'address_building' => null,
            'address_apartment' => null,
            'address_postal_code' => fake()->postcode(),
            'address_full' => null,
            'is_active' => true,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ];
    }

    /**
     * Склад по умолчанию
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'name' => 'Основной склад',
            'code' => 'WH-MAIN',
        ]);
    }

    /**
     * Активный склад
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Неактивный склад
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Удалённый склад
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }

    /**
     * Склад с полным адресом
     */
    public function withFullAddress(): static
    {
        return $this->state(fn (array $attributes) => [
            'address_full' => "Россия, {$attributes['address_region']}, г. {$attributes['address_city']}, ул. {$attributes['address_street']}, д. {$attributes['address_house']}",
        ]);
    }
}
