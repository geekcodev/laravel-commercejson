<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use GeekCo\CommerceJson\Enums\PropertyTypeEnum;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Distinct;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class PropertyDefinitionData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Required, StringType]
        public string $name,
        #[Required, Enum(PropertyTypeEnum::class)]
        public PropertyTypeEnum $type,
        #[Nullable, StringType]
        public ?string $code = null,
        #[Nullable, StringType]
        public ?string $unit = null,
        #[Nullable, BooleanType]
        public ?bool $is_filterable = null,
        #[Nullable, BooleanType]
        public ?bool $is_required = null,
        #[Nullable, BooleanType]
        public ?bool $use_for_catalog = null,
        #[Nullable, BooleanType]
        public ?bool $use_for_offers = null,
        #[Nullable, BooleanType]
        public ?bool $use_for_documents = null,
        #[Nullable, ArrayType, Distinct]
        public ?array $enum_values = null,
        #[Nullable, BooleanType]
        public ?bool $applies_to_all = null,
        #[Nullable, ArrayType, Distinct]
        public ?array $category_ids = null,
    ) {}
}
