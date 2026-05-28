<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class ProductVariantData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Nullable, StringType]
        public ?string $external_id,
        #[Required, StringType]
        public string $name,
        #[Nullable, StringType]
        public ?string $code = null,
        #[Nullable, StringType]
        public ?string $barcode = null,
        #[Nullable, ArrayType, DataCollectionOf(PropertyValueData::class)]
        public ?array $properties = null,
        #[Nullable, BooleanType]
        public ?bool $is_active = null,
    ) {}
}
