<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class StockData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $warehouse_id,
        #[Required, Numeric, Min(0)]
        public float $quantity,
        #[Nullable, Numeric, Min(0)]
        public ?float $quantity_reserved = null,
    ) {}
}
