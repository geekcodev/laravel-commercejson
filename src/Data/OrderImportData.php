<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class OrderImportData extends Data
{
    public function __construct(
        #[Required, ArrayType, Min(1), DataCollectionOf(OrderBulkUpdateItemData::class)]
        public array $orders,
    ) {}
}
