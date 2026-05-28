<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
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

class OrderData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Nullable, StringType]
        public ?string $number,
        #[Nullable, StringType]
        public ?string $external_id,
        #[Required, Enum(OrderStatusEnum::class)]
        public OrderStatusEnum $status,
        #[Nullable, Enum(DocumentTypeEnum::class)]
        public ?DocumentTypeEnum $document_type,
        #[Nullable, Enum(PartyRoleEnum::class)]
        public ?PartyRoleEnum $role,
        #[Nullable, Enum(CurrencyEnum::class)]
        public ?CurrencyEnum $base_currency,
        #[Nullable, Numeric, Min(0)]
        public ?float $exchange_rate,
        #[Nullable, StringType]
        public ?string $payment_terms,
        #[Nullable, StringType, Uuid]
        public ?string $counterparty_id,
        #[Nullable]
        public ?OrderCustomerData $customer,
        #[Nullable]
        public ?OrderDeliveryData $delivery,
        #[Nullable]
        public ?OrderPaymentData $payment,
        #[Required, ArrayType, Min(1), DataCollectionOf(OrderItemData::class)]
        public array $items,
        #[Required]
        public OrderTotalsData $totals,
        #[Nullable, StringType, Uuid]
        public ?string $warehouse_id,
        #[Nullable, ArrayType]
        public ?array $linked_documents = null,
        #[Nullable, ArrayType, DataCollectionOf(CustomAttributeData::class)]
        public ?array $custom_attributes = null,
        #[Nullable, ArrayType, DataCollectionOf(SignatoryData::class)]
        public ?array $signatories = null,
        #[Nullable, StringType]
        public ?string $comment = null,
        #[Nullable, ArrayType, DataCollectionOf(StatusHistoryEntryData::class)]
        public ?array $status_history = null,
        #[Nullable, StringType]
        public ?Carbon $created_at = null,
        #[Nullable, StringType]
        public ?Carbon $updated_at = null,
        #[Nullable, StringType]
        public ?Carbon $deleted_at = null,
    ) {}
}
