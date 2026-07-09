<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class OrderTotalsData extends Data
{
    public function __construct(
        #[Required]
        public MoneyData $subtotal,
        #[Required]
        public MoneyData $total,
        #[Nullable]
        public ?MoneyData $discount = null,
        #[Nullable]
        public ?MoneyData $delivery = null,
        #[Nullable]
        public ?MoneyData $tax = null,
    ) {}
}
