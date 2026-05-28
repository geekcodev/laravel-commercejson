<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class MoneyData extends Data
{
    public function __construct(
        #[Required, StringType, Regex('/^-?\d+(\.\d{1,2})?$/')]
        public string $amount,
        #[Required, Enum(CurrencyEnum::class)]
        public CurrencyEnum $currency
    ) {}
}
