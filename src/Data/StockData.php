<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class StockData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $warehouseId,
        #[Required, Numeric, Min(0)]
        public float $quantity,
        #[Nullable, Numeric, Min(0)]
        public ?float $quantityReserved = null,
    ) {}
}
