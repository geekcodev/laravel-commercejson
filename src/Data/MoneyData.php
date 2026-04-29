<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class MoneyData extends Data
{
    public function __construct(
        #[Required, StringType, Regex('/^-?\d+(\.\d{1,2})?$/')]
        public string $amount,
        #[Required, Enum(CurrencyEnum::class)]
        public CurrencyEnum $currency
    ) {}
}
