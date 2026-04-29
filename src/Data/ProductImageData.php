<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ProductImageData extends Data
{
    public function __construct(
        #[Required, StringType, Url]
        public string $url,
        #[Nullable, IntegerType]
        public ?int $sort = null,
        #[Nullable, StringType]
        public ?string $alt = null,
        #[Nullable, BooleanType]
        public ?bool $isMain = null,
    ) {}
}
