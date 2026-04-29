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
class ImportErrorData extends Data
{
    public function __construct(
        #[Nullable, StringType]
        public ?string $id,
        #[Nullable, StringType]
        public ?string $code,
        #[Required, StringType]
        public string $message
    ) {}
}
