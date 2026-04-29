<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class SignatoryData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $firstName,
        #[Required, StringType]
        public string $lastName,
        #[Nullable, StringType]
        public ?string $middleName = null,
        #[Nullable, StringType]
        public ?string $position = null,
        #[Nullable, StringType]
        public ?string $basis = null,
    ) {}
}
