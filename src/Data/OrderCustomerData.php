<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
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
        public ?string $counterpartyId = null,
    ) {}
}
