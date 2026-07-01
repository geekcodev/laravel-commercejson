<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Models\CustomAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomAttribute>
 */
class CustomAttributeFactory extends CommerceJsonFactory
{
    protected $model = CustomAttribute::class;

    public function definition(): array
    {
        return [
            'id' => static::uuid(),
            'attributable_type' => 'counterparty',
            'attributable_id' => CounterpartyFactory::new(),
            'key' => fake()->unique()->word(),
            'value_string' => fake()->word(),
            'value_number' => null,
            'value_boolean' => null,
            'value_json' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function stringValue(string $key, string $value): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value_string' => $value,
            'value_number' => null,
            'value_boolean' => null,
            'value_json' => null,
        ]);
    }

    public function numberValue(string $key, int|float $value): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value_string' => null,
            'value_number' => $value,
            'value_boolean' => null,
            'value_json' => null,
        ]);
    }

    public function booleanValue(string $key, bool $value): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value_string' => null,
            'value_number' => null,
            'value_boolean' => $value,
            'value_json' => null,
        ]);
    }
}
