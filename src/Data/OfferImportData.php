<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class OfferImportData extends Data
{
    public function __construct(
        #[Required, ArrayType, Min(1), DataCollectionOf(OfferData::class)]
        public array $offers,
        #[Nullable]
        public ?Carbon $valid_from = null,
        #[Nullable]
        public ?Carbon $valid_to = null,
        #[Nullable, ArrayType, DataCollectionOf(PriceTypeData::class)]
        public ?array $price_types = null,
        #[Nullable, ArrayType, DataCollectionOf(WarehouseData::class)]
        public ?array $warehouses = null,
    ) {}
}
