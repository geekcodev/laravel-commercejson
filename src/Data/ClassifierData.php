<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

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
        public ?string $owner_id = null,
        #[Nullable, ArrayType, DataCollectionOf(CategoryData::class)]
        public ?array $categories = null,
        #[Nullable, ArrayType, DataCollectionOf(PropertyDefinitionData::class)]
        public ?array $properties = null,
        #[Nullable, ArrayType, DataCollectionOf(PriceTypeData::class)]
        public ?array $price_types = null,
        #[Nullable, ArrayType, DataCollectionOf(SignatoryData::class)]
        public ?array $signatories = null,
        #[Nullable, StringType]
        public ?Carbon $updated_at = null,
        #[Nullable, StringType]
        public ?Carbon $deleted_at = null,
    ) {}
}
