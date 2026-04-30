<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Enums\ContactTypeEnum;
use GeekCo\CommerceJson\Models\Contact;
use GeekCo\CommerceJson\Models\Counterparty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends CommerceJsonFactory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'id' => static::uuid(),
            'counterparty_id' => CounterpartyFactory::new(),
            'type' => ContactTypeEnum::Email->value,
            'value' => static::generateEmail(),
            'comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Контакт для контрагента
     */
    public function forCounterparty(?Counterparty $counterparty = null): static
    {
        return $this->state(fn (array $attributes) => [
            'counterparty_id' => $counterparty?->id ?? CounterpartyFactory::new()->create()->id,
        ]);
    }

    /**
     * Email контакт
     */
    public function email(?string $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ContactTypeEnum::Email->value,
            'value' => $value ?? static::generateEmail(),
        ]);
    }

    /**
     * Телефон
     */
    public function phone(?string $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ContactTypeEnum::Phone->value,
            'value' => $value ?? static::generatePhone(),
        ]);
    }

    /**
     * Мобильный телефон
     */
    public function mobile(?string $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ContactTypeEnum::Mobile->value,
            'value' => $value ?? static::generatePhone(),
        ]);
    }
}
