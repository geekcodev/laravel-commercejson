<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use GeekCo\CommerceJson\Enums\PropertyTypeEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Distinct;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class PropertyDefinitionData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Required, StringType]
        public string $name,
        #[Nullable, StringType]
        public ?string $code,
        #[Required, Enum(PropertyTypeEnum::class)]
        public PropertyTypeEnum $type,
        #[Nullable, StringType]
        public ?string $unit = null,
        #[Nullable, BooleanType]
        public ?bool $isFilterable = null,
        #[Nullable, BooleanType]
        public ?bool $isRequired = null,
        #[Nullable, BooleanType]
        public ?bool $useForCatalog = null,
        #[Nullable, BooleanType]
        public ?bool $useForOffers = null,
        #[Nullable, BooleanType]
        public ?bool $useForDocuments = null,
        #[Nullable, ArrayType, Distinct]
        public ?array $enumValues = null,
        #[Nullable, BooleanType]
        public ?bool $appliesToAll = null,
        #[Nullable, ArrayType, Distinct]
        public ?array $categoryIds = null,
    ) {}
}
