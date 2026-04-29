<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class CapabilitiesData extends Data
{
    public function __construct(
        #[Nullable, BooleanType]
        public ?bool $catalog = false,
        #[Nullable, BooleanType]
        public ?bool $offers = false,
        #[Nullable, BooleanType]
        public ?bool $orders = false,
        #[Nullable, BooleanType]
        public ?bool $counterparties = false,
        #[Nullable, BooleanType]
        public ?bool $warehouses = false,
        #[Nullable, BooleanType]
        public ?bool $deltaSync = false,
        #[Nullable, BooleanType]
        public ?bool $idempotency = false,
        #[Nullable, IntegerType, Min(1), Max(1000)]
        public ?int $maxPageSize = null,
    ) {}
}
