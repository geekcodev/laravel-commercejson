<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class OrderItemUpdateData extends Data
{
    public function __construct(
        #[Nullable, StringType, Uuid]
        public ?string $id = null,
        #[Nullable, StringType, Uuid]
        public ?string $product_id = null,
        #[Nullable, StringType, Uuid]
        public ?string $variant_id = null,
        #[Nullable, Numeric, GreaterThan(0)]
        public ?float $quantity = null,
        #[Nullable]
        public ?MoneyData $price = null,
    ) {}
}
