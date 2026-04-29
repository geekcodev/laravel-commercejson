<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
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
class OfferPriceData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $priceTypeId,
        #[Required]
        public MoneyData $price,
        #[Nullable]
        public ?MoneyData $priceWithDiscount = null,
        #[Nullable, Numeric, Min(0), Max(100)]
        public ?float $discountPercent = null,
        #[Nullable, Numeric, GreaterThan(0)]
        public ?float $minQuantity = null,
        #[Nullable]
        public ?UnitData $unit = null,
        #[Nullable, StringType]
        public ?Carbon $validFrom = null,
        #[Nullable, StringType]
        public ?Carbon $validTo = null,
    ) {}
}
