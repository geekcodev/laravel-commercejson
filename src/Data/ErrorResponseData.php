<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ErrorResponseData extends Data
{
    public function __construct(
        #[Required]
        public mixed $error
    ) {}
}
