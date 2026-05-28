<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class CounterpartyListData extends Data
{
    public function __construct(
        #[Required, ArrayType, DataCollectionOf(CounterpartyData::class)]
        public array $counterparties,
        #[Required]
        public PaginationData $pagination
    ) {}
}
