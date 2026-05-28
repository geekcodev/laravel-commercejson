<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class OrderListData extends Data
{
    public function __construct(
        #[Required, ArrayType, DataCollectionOf(OrderData::class)]
        public array $orders,
        #[Required]
        public PaginationData $pagination
    ) {}
}
