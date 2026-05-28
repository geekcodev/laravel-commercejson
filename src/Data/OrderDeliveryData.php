<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Enums\DeliveryMethodEnum;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class OrderDeliveryData extends Data
{
    public function __construct(
        #[Required, Enum(DeliveryMethodEnum::class)]
        public DeliveryMethodEnum $type,
        #[Nullable]
        public ?AddressData $address = null,
        #[Nullable, StringType]
        public ?string $method_id = null,
        #[Nullable, StringType]
        public ?string $method_name = null,
        #[Nullable]
        public ?MoneyData $cost = null,
        #[Nullable, StringType]
        public ?string $tracking_number = null,
        #[Nullable, StringType]
        public ?Carbon $shipped_at = null,
        #[Nullable, StringType]
        public ?Carbon $estimated_date = null,
    ) {}
}
