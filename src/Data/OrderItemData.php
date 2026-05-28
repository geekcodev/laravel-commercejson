<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class OrderItemData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Required, StringType, Uuid]
        public string $product_id,
        #[Nullable, StringType, Uuid]
        public ?string $variant_id,
        #[Nullable, StringType]
        public ?string $product_name,
        #[Nullable, StringType]
        public ?string $product_code,
        #[Required, Numeric, GreaterThan(0)]
        public float $quantity,
        #[Nullable]
        public ?UnitData $unit,
        #[Required]
        public MoneyData $price,
        #[Nullable]
        public ?MoneyData $discount,
        #[Required]
        public MoneyData $total,
        #[Nullable, StringType, Regex('/^[A-Z]{2}$/')]
        public ?string $country_of_origin = null,
        #[Nullable, StringType, Max(50)]
        public ?string $customs_declaration_number = null,
        #[Nullable, Numeric, Min(0)]
        public ?float $tax_rate = null,
        #[Nullable, ArrayType, DataCollectionOf(OrderItemTaxData::class)]
        public ?array $taxes = null,
        #[Nullable]
        public ?MoneyData $excise_per_unit = null,
        #[Nullable]
        public ?MoneyData $tax_amount = null,
    ) {}
}
