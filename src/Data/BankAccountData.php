<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class BankAccountData extends Data
{
    public function __construct(
        #[Nullable, StringType]
        public ?string $bankName,
        #[Required, StringType, Regex('/^\d{9}$/')]
        public string $bik,
        #[Required, StringType, Regex('/^\d{20}$/')]
        public string $account,
        #[Nullable, StringType, Regex('/^\d{20}$/')]
        public ?string $corrAccount = null,
        #[Nullable, StringType]
        public ?string $swift = null,
        #[Nullable, BooleanType]
        public ?bool $isDefault = false,
    ) {}
}
