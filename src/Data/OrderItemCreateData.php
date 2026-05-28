<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class OrderItemCreateData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $product_id,
        #[Nullable, StringType, Uuid]
        public ?string $variant_id,
        #[Required, Numeric, GreaterThan(0)]
        public float $quantity
    ) {}
}
