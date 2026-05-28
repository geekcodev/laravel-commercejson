<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class SeoMetaData extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(255)]
        public ?string $title = null,
        #[Nullable, StringType, Max(1000)]
        public ?string $description = null,
        #[Nullable, StringType]
        public ?string $keywords = null
    ) {}
}
