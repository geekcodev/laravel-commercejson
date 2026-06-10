<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
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
        #[Required, Enum(DocumentTypeEnum::class)]
        public DocumentTypeEnum $document_type,
        #[Required, ArrayType, Min(1), DataCollectionOf(OrderItemCreateData::class)]
        public array $items,
        #[Nullable, Enum(PartyRoleEnum::class)]
        public ?PartyRoleEnum $role = null,
        #[Nullable]
        public ?OrderCustomerData $customer = null,
        #[Nullable, StringType, Uuid]
        public ?string $counterparty_id = null,
        #[Nullable]
        public ?OrderDeliveryData $delivery = null,
        #[Nullable]
        public ?OrderPaymentData $payment = null,
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
