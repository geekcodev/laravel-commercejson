<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'documentable_type' => (new Counterparty)->getMorphClass(),
            'documentable_id' => (string) Str::uuid(),
            'external_id' => (string) Str::uuid(),
            'type' => 'contract',
            'name' => $this->faker->words(3, true),
            'file_name' => 'document.pdf',
            'file_path' => 'commercejson/documents/test/document.pdf',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'description' => null,
        ];
    }
}
