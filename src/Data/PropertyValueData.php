<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class PropertyValueData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $property_id,
        #[Required]
        public mixed $value
    ) {}
}
