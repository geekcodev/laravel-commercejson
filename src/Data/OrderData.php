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
        #[Required, Enum(OrderStatusEnum::class)]
        public OrderStatusEnum $status,
        #[Required, ArrayType, Min(1), DataCollectionOf(OrderItemData::class)]
        public array $items,
        #[Required]
        public OrderTotalsData $totals,
        #[Nullable, StringType]
        public ?string $number = null,
        #[Nullable, StringType]
        public ?string $external_id = null,
        #[Nullable, Enum(DocumentTypeEnum::class)]
        public ?DocumentTypeEnum $document_type = null,
        #[Nullable, Enum(PartyRoleEnum::class)]
        public ?PartyRoleEnum $role = null,
        #[Nullable, Enum(CurrencyEnum::class)]
        public ?CurrencyEnum $base_currency = null,
        #[Nullable, Numeric, Min(0)]
        public ?float $exchange_rate = null,
        #[Nullable, StringType]
        public ?string $payment_terms = null,
        #[Nullable, StringType, Uuid]
        public ?string $counterparty_id = null,
        #[Nullable]
        public ?OrderCustomerData $customer = null,
        #[Nullable]
        public ?OrderDeliveryData $delivery = null,
        #[Nullable]
        public ?OrderPaymentData $payment = null,
        #[Nullable, StringType, Uuid]
        public ?string $warehouse_id = null,
        #[Nullable, ArrayType, DataCollectionOf(LinkedDocumentData::class)]
        public ?array $linked_documents = null,
        #[Nullable, ArrayType, DataCollectionOf(CustomAttributeData::class)]
        public ?array $custom_attributes = null,
        #[Nullable, ArrayType, DataCollectionOf(SignatoryData::class)]
        public ?array $signatories = null,
        #[Nullable, StringType]
        public ?string $comment = null,
        #[Nullable, ArrayType, DataCollectionOf(StatusHistoryEntryData::class)]
        public ?array $status_history = null,
        #[Nullable]
        public ?Carbon $created_at = null,
        #[Nullable]
        public ?Carbon $updated_at = null,
        #[Nullable]
        public ?Carbon $deleted_at = null,
    ) {}
}
