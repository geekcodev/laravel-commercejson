<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ClassifierData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Required, StringType]
        public string $name,
        #[Nullable, StringType]
        public ?string $version = null,
        #[Nullable, StringType, Uuid]
        public ?string $ownerId = null,
        #[Nullable, ArrayType, DataCollectionOf(CategoryData::class)]
        public ?array $categories = null,
        #[Nullable, ArrayType, DataCollectionOf(PropertyDefinitionData::class)]
        public ?array $properties = null,
        #[Nullable, ArrayType, DataCollectionOf(PriceTypeData::class)]
        public ?array $priceTypes = null,
        #[Nullable, ArrayType, DataCollectionOf(SignatoryData::class)]
        public ?array $signatories = null,
        #[Nullable, StringType]
        public ?Carbon $updatedAt = null,
        #[Nullable, StringType]
        public ?Carbon $deletedAt = null,
    ) {}
}
