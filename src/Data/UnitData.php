<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use GeekCo\CommerceJson\Enums\OkeiEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class UnitData extends Data
{
    public function __construct(
        #[Nullable, Enum(OkeiEnum::class)]
        public ?OkeiEnum $code = null,
        #[Nullable, StringType]
        public ?string $shortName = null,
        #[Nullable, StringType]
        public ?string $fullName = null,
        #[Nullable, StringType]
        public ?string $international = null,
    ) {}
}
