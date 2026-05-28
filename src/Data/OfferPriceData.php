<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class OfferPriceData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $price_type_id,
        #[Required]
        public MoneyData $price,
        #[Nullable]
        public ?MoneyData $price_with_discount = null,
        #[Nullable, Numeric, Min(0), Max(100)]
        public ?float $discount_percent = null,
        #[Nullable, Numeric, GreaterThan(0)]
        public ?float $min_quantity = null,
        #[Nullable]
        public ?UnitData $unit = null,
        #[Nullable, StringType]
        public ?Carbon $valid_from = null,
        #[Nullable, StringType]
        public ?Carbon $valid_to = null,
    ) {}
}
