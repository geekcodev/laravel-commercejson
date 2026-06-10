<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
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

class ProductData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Required, StringType, Max(255)]
        public string $name,
        #[Required, StringType, Uuid]
        public string $category_id,
        #[Nullable, StringType]
        public ?string $external_id = null,
        #[Nullable, StringType]
        public ?string $code = null,
        #[Nullable, StringType, Max(14)]
        public ?string $barcode = null,
        #[Nullable, StringType]
        public ?string $description = null,
        #[Nullable, StringType, Max(500)]
        public ?string $short_description = null,
        #[Nullable, ArrayType, DataCollectionOf(ProductImageData::class)]
        public ?array $images = null,
        #[Nullable, ArrayType, DataCollectionOf(PropertyValueData::class)]
        public ?array $properties = null,
        #[Nullable, ArrayType, DataCollectionOf(ProductVariantData::class)]
        public ?array $variants = null,
        #[Nullable, Numeric, Min(0)]
        public ?float $tax_rate = null,
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
        #[Nullable, ArrayType, DataCollectionOf(ProductComponentData::class)]
        public ?array $components = null,
        #[Nullable, ArrayType, DataCollectionOf(CustomAttributeData::class)]
        public ?array $custom_attributes = null,
        #[Nullable, BooleanType]
        public ?bool $is_active = null,
        #[Nullable]
        public ?SeoMetaData $seo = null,
        #[Nullable]
        public ?Carbon $created_at = null,
        #[Nullable]
        public ?Carbon $updated_at = null,
        #[Nullable]
        public ?Carbon $deleted_at = null,
    ) {}
}
