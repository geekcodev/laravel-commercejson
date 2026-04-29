<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Enums\DeliveryMethodEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class OrderDeliveryData extends Data
{
    public function __construct(
        #[Required, Enum(DeliveryMethodEnum::class)]
        public DeliveryMethodEnum $type,
        #[Nullable]
        public ?AddressData $address = null,
        #[Nullable, StringType]
        public ?string $methodId = null,
        #[Nullable, StringType]
        public ?string $methodName = null,
        #[Nullable]
        public ?MoneyData $cost = null,
        #[Nullable, StringType]
        public ?string $trackingNumber = null,
        #[Nullable, StringType]
        public ?Carbon $shippedAt = null,
        #[Nullable, StringType]
        public ?Carbon $estimatedDate = null,
    ) {}
}
