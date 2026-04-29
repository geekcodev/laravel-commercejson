<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Enums\PartyRoleEnum;
use GeekCo\CommerceJson\Enums\PaymentMethodEnum;
use GeekCo\CommerceJson\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'number',
        'external_id',
        'status',
        'document_type',
        'role',
        'base_currency',
        'exchange_rate',
        'payment_terms',
        'counterparty_id',
        'warehouse_id',
        'comment',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_counterparty_id',
        'delivery_type',
        'delivery_address_country',
        'delivery_address_region',
        'delivery_address_district',
        'delivery_address_city',
        'delivery_address_street',
        'delivery_address_house',
        'delivery_address_building',
        'delivery_address_apartment',
        'delivery_address_postal_code',
        'delivery_address_full',
        'delivery_method_id',
        'delivery_method_name',
        'delivery_cost_amount',
        'delivery_cost_currency',
        'delivery_tracking_number',
        'delivery_shipped_at',
        'delivery_estimated_date',
        'payment_type',
        'payment_status',
        'payment_amount',
        'payment_currency',
        'payment_paid_at',
        'payment_transaction_id',
        'totals_subtotal_amount',
        'totals_subtotal_currency',
        'totals_discount_amount',
        'totals_discount_currency',
        'totals_delivery_amount',
        'totals_delivery_currency',
        'totals_tax_amount',
        'totals_tax_currency',
        'totals_total_amount',
        'totals_total_currency',
    ];

    protected $casts = [
        'status' => OrderStatusEnum::class,
        'document_type' => DocumentTypeEnum::class,
        'role' => PartyRoleEnum::class,
        'base_currency' => CurrencyEnum::class,
        'exchange_rate' => 'decimal:4',
        'delivery_cost_amount' => 'decimal:2',
        'delivery_cost_currency' => CurrencyEnum::class,
        'delivery_shipped_at' => 'datetime',
        'delivery_estimated_date' => 'date',
        'payment_type' => PaymentMethodEnum::class,
        'payment_status' => PaymentStatusEnum::class,
        'payment_amount' => 'decimal:2',
        'payment_currency' => CurrencyEnum::class,
        'payment_paid_at' => 'datetime',
        'totals_subtotal_amount' => 'decimal:2',
        'totals_subtotal_currency' => CurrencyEnum::class,
        'totals_discount_amount' => 'decimal:2',
        'totals_discount_currency' => CurrencyEnum::class,
        'totals_delivery_amount' => 'decimal:2',
        'totals_delivery_currency' => CurrencyEnum::class,
        'totals_tax_amount' => 'decimal:2',
        'totals_tax_currency' => CurrencyEnum::class,
        'totals_total_amount' => 'decimal:2',
        'totals_total_currency' => CurrencyEnum::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class);
    }

    public function customerCounterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'customer_counterparty_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(StatusHistoryEntry::class);
    }

    public function customAttributes(): MorphMany
    {
        return $this->morphMany(CustomAttribute::class, 'attributable');
    }

    public function signatories(): MorphMany
    {
        return $this->morphMany(Signatory::class, 'signatory');
    }

    public function linkedDocuments(): BelongsToMany
    {
        return $this->belongsToMany(
            Order::class,
            'order_linked_documents',
            'order_id',
            'linked_order_id'
        )->withPivot(['external_id', 'type'])->withTimestamps();
    }

    public function linkedDocumentsFrom(): BelongsToMany
    {
        return $this->belongsToMany(
            Order::class,
            'order_linked_documents',
            'linked_order_id',
            'order_id'
        )->withPivot(['external_id', 'type'])->withTimestamps();
    }
}
