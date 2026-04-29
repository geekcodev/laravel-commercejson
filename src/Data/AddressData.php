<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
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
        public ?string $postalCode = null,
        #[Nullable, StringType]
        public ?string $full = null,
    ) {}
}
