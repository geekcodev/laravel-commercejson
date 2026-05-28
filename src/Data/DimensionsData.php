<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Data;

class DimensionsData extends Data
{
    public function __construct(
        #[Nullable, Numeric, Min(0)]
        public ?float $length = null,
        #[Nullable, Numeric, Min(0)]
        public ?float $width = null,
        #[Nullable, Numeric, Min(0)]
        public ?float $height = null,
    ) {}
}
