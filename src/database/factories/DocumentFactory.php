<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\Document;

class DocumentFactory extends CommerceJsonFactory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'id' => static::uuid(),
            'documentable_type' => (new Counterparty)->getMorphClass(),
            'documentable_id' => static::uuid(),
            'external_id' => static::externalId(),
            'type' => 'contract',
            'name' => fake()->words(3, true),
            'file_name' => 'document.pdf',
            'file_path' => 'commercejson/documents/test/document.pdf',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'description' => null,
            'paid' => fake()->boolean(50),
            'document_date' => fake()->date(),
            'document_amount_amount' => fake()->randomFloat(2, 1000, 500000),
            'document_amount_currency' => CurrencyEnum::RUB->value,
        ];
    }
}
