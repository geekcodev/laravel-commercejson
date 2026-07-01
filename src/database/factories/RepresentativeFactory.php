<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\Representative;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Representative>
 */
class RepresentativeFactory extends CommerceJsonFactory
{
    protected $model = Representative::class;

    public function definition(): array
    {
        return [
            'id' => static::uuid(),
            'counterparty_id' => CounterpartyFactory::new(),
            'name' => fake()->name(),
            'relation' => fake()->randomElement(['CEO', 'Manager', 'Accountant', 'Sales Rep', 'Support']),
            'phone' => static::generatePhone(),
            'email' => static::generateEmail(),
            'position' => fake()->jobTitle(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forCounterparty(?Counterparty $counterparty = null): static
    {
        return $this->state(fn (array $attributes) => [
            'counterparty_id' => $counterparty?->id ?? CounterpartyFactory::new()->create()->id,
        ]);
    }
}
