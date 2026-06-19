<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\DeliveryMethodEnum;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Enums\PartyRoleEnum;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\OrderItem;
use Illuminate\Database\Eloquent\Collection;
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
use Spatie\LaravelData\DataCollection;

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

    public static function fromModel(Order $model): static
    {
        $data = [
            'id' => $model->id,
            'status' => $model->status,
            'number' => $model->number,
            'external_id' => $model->external_id,
            'document_type' => $model->document_type,
            'role' => $model->role,
            'base_currency' => $model->base_currency,
            'exchange_rate' => $model->exchange_rate,
            'payment_terms' => $model->payment_terms,
            'counterparty_id' => $model->counterparty_id,
            'warehouse_id' => $model->warehouse_id,
            'comment' => $model->comment,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at,
            'deleted_at' => $model->deleted_at,
        ];

        /** @var Collection<int, OrderItem> $items */
        $items = $model->relationLoaded('items')
            ? $model->items
            : $model->items()->get();

        $data['items'] = $items->map(fn (OrderItem $item): array => [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'quantity' => (float) $item->quantity,
            'price' => MoneyData::from([
                'amount' => (string) $item->price_amount,
                'currency' => $item->price_currency,
            ]),
            'total' => MoneyData::from([
                'amount' => (string) $item->total_amount,
                'currency' => $item->total_currency,
            ]),
            'variant_id' => $item->variant_id,
            'product_name' => $item->product_name,
            'product_code' => $item->product_code,
            'unit' => $item->unit_code ? UnitData::from([
                'code' => $item->unit_code,
                'short_name' => $item->unit_short_name,
                'full_name' => $item->unit_full_name,
                'international' => $item->unit_international,
            ]) : null,
            'discount' => $item->discount_amount !== null ? MoneyData::from([
                'amount' => (string) $item->discount_amount,
                'currency' => $item->discount_currency,
            ]) : null,
            'country_of_origin' => $item->country_of_origin,
            'customs_declaration_number' => $item->customs_declaration_number,
            'tax_rate' => $item->tax_rate,
            'taxes' => $item->relationLoaded('taxes')
                ? OrderItemTaxData::collect($item->taxes, DataCollection::class)
                : null,
        ])->toArray();

        $data['totals'] = OrderTotalsData::from([
            'subtotal' => MoneyData::from([
                'amount' => (string) ($model->totals_subtotal_amount ?? '0'),
                'currency' => $model->totals_subtotal_currency ?? $model->base_currency ?? CurrencyEnum::RUB,
            ]),
            'total' => MoneyData::from([
                'amount' => (string) ($model->totals_total_amount ?? '0'),
                'currency' => $model->totals_total_currency ?? $model->base_currency ?? CurrencyEnum::RUB,
            ]),
            'discount' => $model->totals_discount_amount !== null ? MoneyData::from([
                'amount' => (string) $model->totals_discount_amount,
                'currency' => $model->totals_discount_currency,
            ]) : null,
            'delivery' => $model->totals_delivery_amount !== null ? MoneyData::from([
                'amount' => (string) $model->totals_delivery_amount,
                'currency' => $model->totals_delivery_currency,
            ]) : null,
            'tax' => $model->totals_tax_amount !== null ? MoneyData::from([
                'amount' => (string) $model->totals_tax_amount,
                'currency' => $model->totals_tax_currency,
            ]) : null,
        ]);

        if ($model->customer_name || $model->customer_phone || $model->customer_email || $model->customer_counterparty_id) {
            $data['customer'] = OrderCustomerData::from([
                'name' => $model->customer_name,
                'phone' => $model->customer_phone,
                'email' => $model->customer_email,
                'counterparty_id' => $model->customer_counterparty_id,
            ]);
        }

        if ($model->delivery_type) {
            $delivery = [
                'type' => $model->delivery_type,
                'method_id' => $model->delivery_method_id,
                'method_name' => $model->delivery_method_name,
                'tracking_number' => $model->delivery_tracking_number,
                'shipped_at' => $model->delivery_shipped_at,
                'estimated_date' => $model->delivery_estimated_date,
            ];

            if ($model->delivery_cost_amount !== null) {
                $delivery['cost'] = MoneyData::from([
                    'amount' => (string) $model->delivery_cost_amount,
                    'currency' => $model->delivery_cost_currency,
                ]);
            }

            $hasAddress = $model->delivery_address_country || $model->delivery_address_city || $model->delivery_address_full;
            $deliveryMethod = DeliveryMethodEnum::tryFrom($model->delivery_type);
            $requiresAddress = $deliveryMethod?->requiresAddress() ?? false;

            if ($hasAddress || $requiresAddress) {
                $delivery['address'] = AddressData::from([
                    'country' => $model->delivery_address_country,
                    'region' => $model->delivery_address_region,
                    'district' => $model->delivery_address_district,
                    'city' => $model->delivery_address_city,
                    'street' => $model->delivery_address_street,
                    'house' => $model->delivery_address_house,
                    'building' => $model->delivery_address_building,
                    'apartment' => $model->delivery_address_apartment,
                    'postal_code' => $model->delivery_address_postal_code,
                    'full' => $model->delivery_address_full,
                ]);
            }

            $data['delivery'] = OrderDeliveryData::from($delivery);
        }

        if ($model->payment_type) {
            $payment = [
                'type' => $model->payment_type,
                'status' => $model->payment_status,
                'paid_at' => $model->payment_paid_at,
            ];

            if ($model->payment_amount !== null) {
                $payment['amount'] = MoneyData::from([
                    'amount' => (string) $model->payment_amount,
                    'currency' => $model->payment_currency,
                ]);
            }

            $data['payment'] = OrderPaymentData::from($payment);
        }

        if ($model->relationLoaded('status_history')) {
            $data['status_history'] = StatusHistoryEntryData::collect($model->status_history, DataCollection::class);
        }

        if ($model->relationLoaded('linked_documents')) {
            $data['linked_documents'] = LinkedDocumentData::collect($model->linked_documents, DataCollection::class);
        }

        if ($model->relationLoaded('custom_attributes')) {
            $data['custom_attributes'] = CustomAttributeData::collect($model->custom_attributes, DataCollection::class);
        }

        if ($model->relationLoaded('signatories')) {
            $data['signatories'] = SignatoryData::collect($model->signatories, DataCollection::class);
        }

        return static::from($data);
    }
}
