<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Enums\DeliveryMethodEnum;
use Illuminate\Validation\Validator;
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
        #[Nullable]
        public ?Carbon $shipped_at = null,
        #[Nullable]
        public ?Carbon $estimated_date = null,
    ) {}

    public static function withValidator(Validator $validator): void
    {
        $data = $validator->getData();

        $type = $data['type'] ?? null;
        if (is_object($type) && method_exists($type, 'value')) {
            $type = $type->value;
        }

        $requiresAddress = in_array($type, ['courier', 'post', 'transport_company'], true);

        if ($requiresAddress && empty($data['address'] ?? null)) {
            $validator->errors()->add('address', 'Address is required for delivery type '.($type ?? 'unknown'));
        }
    }
}
