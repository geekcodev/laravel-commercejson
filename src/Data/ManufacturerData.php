<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class ManufacturerData extends Data
{
    public function __construct(
        #[Nullable, StringType, Regex('/^[A-Z]{2}$/')]
        public ?string $country = null,
        #[Nullable, StringType]
        public ?string $brand = null,
        #[Nullable, StringType, Uuid]
        public ?string $brand_owner_id = null,
        #[Nullable, StringType, Uuid]
        public ?string $manufacturer_id = null,
    ) {}
}
