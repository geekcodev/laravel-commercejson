<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class WarehouseData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Nullable, StringType]
        public ?string $externalId,
        #[Required, StringType]
        public string $name,
        #[Nullable, StringType]
        public ?string $code = null,
        #[Nullable]
        public ?AddressData $address = null,
        #[Nullable, BooleanType]
        public ?bool $isActive = null,
        #[Nullable, BooleanType]
        public ?bool $isDefault = null,
        #[Nullable, StringType]
        public ?Carbon $createdAt = null,
        #[Nullable, StringType]
        public ?Carbon $updatedAt = null,
        #[Nullable, StringType]
        public ?Carbon $deletedAt = null,
    ) {}
}
