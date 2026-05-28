<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CustomAttributeData extends Data
{
    public function __construct(
        #[Required, StringType, Max(100)]
        public string $key,
        #[Required]
        public mixed $value
    ) {}
}
