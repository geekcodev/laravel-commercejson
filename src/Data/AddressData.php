<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class AddressData extends Data
{
    public function __construct(
        #[Nullable, StringType]
        public ?string $country = null,
        #[Nullable, StringType]
        public ?string $region = null,
        #[Nullable, StringType]
        public ?string $district = null,
        #[Nullable, StringType]
        public ?string $city = null,
        #[Nullable, StringType]
        public ?string $street = null,
        #[Nullable, StringType]
        public ?string $house = null,
        #[Nullable, StringType]
        public ?string $building = null,
        #[Nullable, StringType]
        public ?string $apartment = null,
        #[Nullable, StringType]
        public ?string $postal_code = null,
        #[Nullable, StringType]
        public ?string $full = null,
    ) {}
}
