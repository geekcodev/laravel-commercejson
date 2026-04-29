<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Enums\PartyRoleEnum;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class OrderData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Nullable, StringType]
        public ?string $number,
        #[Nullable, StringType]
        public ?string $externalId,
        #[Required, Enum(OrderStatusEnum::class)]
        public OrderStatusEnum $status,
        #[Nullable, Enum(DocumentTypeEnum::class)]
        public ?DocumentTypeEnum $documentType,
        #[Nullable, Enum(PartyRoleEnum::class)]
        public ?PartyRoleEnum $role,
        #[Nullable, Enum(CurrencyEnum::class)]
        public ?CurrencyEnum $baseCurrency,
        #[Nullable, Numeric, Min(0)]
        public ?float $exchangeRate,
        #[Nullable, StringType]
        public ?string $paymentTerms,
        #[Nullable, StringType, Uuid]
        public ?string $counterpartyId,
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
        public ?string $warehouseId,
        #[Nullable, ArrayType]
        public ?array $linkedDocuments = null,
        #[Nullable, ArrayType, DataCollectionOf(CustomAttributeData::class)]
        public ?array $customAttributes = null,
        #[Nullable, ArrayType, DataCollectionOf(SignatoryData::class)]
        public ?array $signatories = null,
        #[Nullable, StringType]
        public ?string $comment = null,
        #[Nullable, ArrayType, DataCollectionOf(StatusHistoryEntryData::class)]
        public ?array $statusHistory = null,
        #[Nullable, StringType]
        public ?Carbon $createdAt = null,
        #[Nullable, StringType]
        public ?Carbon $updatedAt = null,
        #[Nullable, StringType]
        public ?Carbon $deletedAt = null,
    ) {}
}
