<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class OrderCustomerData extends Data
{
    public function __construct(
        #[Nullable, StringType]
        public ?string $name = null,
        #[Nullable, StringType]
        public ?string $phone = null,
        #[Nullable, StringType, Email]
        public ?string $email = null,
        #[Nullable, StringType, Uuid]
        public ?string $counterparty_id = null,
    ) {}
}
