<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class SignatoryData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $first_name,
        #[Required, StringType]
        public string $last_name,
        #[Nullable, StringType]
        public ?string $middle_name = null,
        #[Nullable, StringType]
        public ?string $position = null,
        #[Nullable, StringType]
        public ?string $basis = null,
    ) {}
}
