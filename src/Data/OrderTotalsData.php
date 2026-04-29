<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class OrderTotalsData extends Data
{
    public function __construct(
        #[Required]
        public MoneyData $subtotal,
        #[Nullable]
        public ?MoneyData $discount,
        #[Nullable]
        public ?MoneyData $delivery,
        #[Nullable]
        public ?MoneyData $tax,
        #[Required]
        public MoneyData $total,
    ) {}
}
