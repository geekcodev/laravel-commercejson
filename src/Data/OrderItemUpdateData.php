<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class OrderItemUpdateData extends Data
{
    public function __construct(
        #[Nullable, StringType, Uuid]
        public ?string $id = null,
        #[Nullable, StringType, Uuid]
        public ?string $productId = null,
        #[Nullable, StringType, Uuid]
        public ?string $variantId = null,
        #[Nullable, Numeric, GreaterThan(0)]
        public ?float $quantity = null,
        #[Nullable]
        public ?MoneyData $price = null,
    ) {}
}
