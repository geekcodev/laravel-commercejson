<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use GeekCo\CommerceJson\Casts\TrimmedEnumCast;
use GeekCo\CommerceJson\Enums\OkeiEnum;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

class UnitData extends Data
{
    public function __construct(
        #[Nullable, Enum(OkeiEnum::class), WithCast(TrimmedEnumCast::class, OkeiEnum::class)]
        public ?OkeiEnum $code = null,
        #[Nullable, StringType]
        public ?string $short_name = null,
        #[Nullable, StringType]
        public ?string $full_name = null,
        #[Nullable, StringType]
        public ?string $international = null,
    ) {}
}
