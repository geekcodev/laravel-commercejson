<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Distinct;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class OfferData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $productId,
        #[Nullable, StringType, Uuid]
        public ?string $variantId,
        #[Required, ArrayType, Min(1)]
        public array $prices,
        #[Nullable, ArrayType, Distinct]
        public ?array $stocks = null,
        #[Nullable, StringType]
        public ?Carbon $updatedAt = null,
        #[Nullable, StringType]
        public ?Carbon $deletedAt = null,
    ) {}
}
