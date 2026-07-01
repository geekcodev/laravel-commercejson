<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class BankAccountData extends Data
{
    public function __construct(
        #[Required, StringType, Regex('/^\d{9}$/')]
        public string $bik,
        #[Required, StringType, Regex('/^\d{20}$/')]
        public string $account,
        #[Nullable, StringType]
        public ?string $bank_name = null,
        #[Nullable, StringType, Regex('/^\d{20}$/')]
        public ?string $corr_account = null,
        #[Nullable, StringType]
        public ?string $swift = null,
        #[Nullable, BooleanType]
        public ?bool $is_default = false,
        #[Nullable, StringType, Uuid]
        public ?string $id = null,
    ) {}
}
