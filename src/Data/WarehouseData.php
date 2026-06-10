<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class WarehouseData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Required, StringType]
        public string $name,
        #[Nullable, StringType]
        public ?string $external_id = null,
        #[Nullable, StringType]
        public ?string $code = null,
        #[Nullable]
        public ?AddressData $address = null,
        #[Nullable, BooleanType]
        public ?bool $is_active = null,
        #[Nullable, BooleanType]
        public ?bool $is_default = null,
        #[Nullable]
        public ?Carbon $created_at = null,
        #[Nullable]
        public ?Carbon $updated_at = null,
        #[Nullable]
        public ?Carbon $deleted_at = null,
    ) {}
}
