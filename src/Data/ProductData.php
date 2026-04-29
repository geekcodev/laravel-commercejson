<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Distinct;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ProductData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Nullable, StringType]
        public ?string $externalId,
        #[Required, StringType, Max(255)]
        public string $name,
        #[Nullable, StringType]
        public ?string $code,
        #[Nullable, StringType, Max(14)]
        public ?string $barcode,
        #[Required, StringType, Uuid]
        public string $categoryId,
        #[Nullable, StringType]
        public ?string $description = null,
        #[Nullable, StringType, Max(500)]
        public ?string $shortDescription = null,
        #[Nullable, ArrayType, DataCollectionOf(ProductImageData::class)]
        public ?array $images = null,
        #[Nullable, ArrayType, DataCollectionOf(PropertyValueData::class)]
        public ?array $properties = null,
        #[Nullable, ArrayType, DataCollectionOf(ProductVariantData::class)]
        public ?array $variants = null,
        #[Nullable, Numeric, Min(0)]
        public ?float $taxRate = null,
        #[Nullable]
        public ?UnitData $unit = null,
        #[Nullable, Numeric, Min(0)]
        public ?float $weight = null,
        #[Nullable]
        public ?DimensionsData $dimensions = null,
        #[Nullable]
        public ?ManufacturerData $manufacturer = null,
        #[Nullable, ArrayType, Distinct]
        public ?array $analogues = null,
        #[Nullable, ArrayType]
        public ?array $components = null,
        #[Nullable, ArrayType, DataCollectionOf(CustomAttributeData::class)]
        public ?array $customAttributes = null,
        #[Nullable, BooleanType]
        public ?bool $isActive = null,
        #[Nullable]
        public ?SeoMetaData $seo = null,
        #[Nullable, StringType]
        public ?Carbon $createdAt = null,
        #[Nullable, StringType]
        public ?Carbon $updatedAt = null,
        #[Nullable, StringType]
        public ?Carbon $deletedAt = null,
    ) {}
}
