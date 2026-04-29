<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ManufacturerData extends Data
{
    public function __construct(
        #[Nullable, StringType, Regex('/^[A-Z]{2}$/')]
        public ?string $country = null,
        #[Nullable, StringType]
        public ?string $brand = null,
        #[Nullable, StringType, Uuid]
        public ?string $brandOwnerId = null,
        #[Nullable, StringType, Uuid]
        public ?string $manufacturerId = null,
    ) {}
}
