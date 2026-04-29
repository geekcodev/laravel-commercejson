<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class CounterpartyListData extends Data
{
    public function __construct(
        #[Required, ArrayType, DataCollectionOf(CounterpartyData::class)]
        public array $counterparties,
        #[Required]
        public PaginationData $pagination
    ) {}
}
