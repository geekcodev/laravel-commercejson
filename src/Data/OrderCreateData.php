<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\PartyRoleEnum;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class OrderCreateData extends Data
{
    public function __construct(
        #[Required]
        public mixed $document_type,
        #[Nullable, Enum(PartyRoleEnum::class)]
        public ?PartyRoleEnum $role,
        #[Nullable]
        public ?OrderCustomerData $customer,
        #[Nullable, StringType, Uuid]
        public ?string $counterparty_id,
        #[Nullable]
        public ?OrderDeliveryData $delivery,
        #[Nullable]
        public ?OrderPaymentData $payment,
        #[Required, ArrayType, Min(1), DataCollectionOf(OrderItemCreateData::class)]
        public array $items,
        #[Required]
        public OrderTotalsData $totals,
        #[Nullable, StringType, Uuid]
        public ?string $warehouse_id = null,
        #[Nullable, Enum(CurrencyEnum::class)]
        public ?CurrencyEnum $base_currency = null,
        #[Nullable, Numeric, Min(0)]
        public ?float $exchange_rate = null,
        #[Nullable, StringType]
        public ?string $payment_terms = null,
        #[Nullable, StringType]
        public ?string $comment = null,
        #[Nullable, ArrayType, DataCollectionOf(CustomAttributeData::class)]
        public ?array $custom_attributes = null,
        #[Nullable, ArrayType, DataCollectionOf(SignatoryData::class)]
        public ?array $signatories = null,
    ) {}
}
