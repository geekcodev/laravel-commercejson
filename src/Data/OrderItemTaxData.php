<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class OrderItemTaxData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $type,
        #[Required, Numeric, Min(0)]
        public float $rate,
        #[Required]
        public MoneyData $amount,
        #[Nullable]
        public ?bool $is_included = true,
    ) {}
}
