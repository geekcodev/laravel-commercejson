<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Distinct;
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
        public ?string $variant_id = null,
        #[Nullable, ArrayType, DataCollectionOf(OfferPriceData::class)]
        public ?array $prices = null,
        #[Nullable, ArrayType, Distinct, DataCollectionOf(StockData::class)]
        public ?array $stocks = null,
        #[Nullable]
        public ?Carbon $updated_at = null,
        #[Nullable]
        public ?Carbon $deleted_at = null,
    ) {}
}
