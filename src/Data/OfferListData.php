<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class OfferListData extends Data
{
    public function __construct(
        #[Nullable, StringType]
        public ?Carbon $validFrom,
        #[Nullable, StringType]
        public ?Carbon $validTo,
        #[Nullable, ArrayType, DataCollectionOf(PriceTypeData::class)]
        public ?array $priceTypes,
        #[Nullable, ArrayType, DataCollectionOf(WarehouseData::class)]
        public ?array $warehouses,
        #[Required, ArrayType, DataCollectionOf(OfferData::class)]
        public array $offers,
        #[Required]
        public PaginationData $pagination
    ) {}
}
