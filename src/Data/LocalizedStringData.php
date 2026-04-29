<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class LocalizedStringData extends Data
{
    public function __construct(
        #[Nullable, StringType]
        public ?string $ru = null,
        #[Nullable, StringType]
        public ?string $en = null,
    ) {}
}
