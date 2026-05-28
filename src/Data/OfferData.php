<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Distinct;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class OfferData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $product_id,
        #[Nullable, StringType, Uuid]
        public ?string $variant_id,
        #[Required, ArrayType, Min(1)]
        public array $prices,
        #[Nullable, ArrayType, Distinct]
        public ?array $stocks = null,
        #[Nullable, StringType]
        public ?Carbon $updated_at = null,
        #[Nullable, StringType]
        public ?Carbon $deleted_at = null,
    ) {}
}
