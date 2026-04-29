<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Models\BankAccount;
use GeekCo\CommerceJson\Models\Counterparty;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends CommerceJsonFactory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'id' => null,
            'counterparty_id' => CounterpartyFactory::new(),
            'bank_name' => fake()->company().' Банк',
            'bik' => static::numerify('#########'),
            'account' => static::numerify('####################'),
            'corr_account' => static::numerify('####################'),
            'swift' => null,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Счёт для контрагента
     */
    public function forCounterparty(?Counterparty $counterparty = null): static
    {
        return $this->state(fn (array $attributes) => [
            'counterparty_id' => $counterparty?->id ?? CounterpartyFactory::new()->create()->id,
        ]);
    }

    /**
     * Счёт по умолчанию
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Счёт с конкретным БИК
     */
    public function withBik(string $bik): static
    {
        return $this->state(fn (array $attributes) => [
            'bik' => $bik,
        ]);
    }

    /**
     * Счёт с конкретным счётом
     */
    public function withAccount(string $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account' => $account,
        ]);
    }
}
