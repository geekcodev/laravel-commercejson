<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use GeekCo\CommerceJson\Database\Factories\OfferPriceFactory;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $price_type_id
 * @property string|null $price_amount
 * @property CurrencyEnum|null $price_currency
 * @property string|null $price_with_discount_amount
 * @property CurrencyEnum|null $price_with_discount_currency
 * @property string|null $discount_percent
 * @property string|null $min_quantity
 * @property string|null $unit_code
 * @property string|null $unit_short_name
 * @property string|null $unit_full_name
 * @property string|null $unit_international
 * @property \DateTime|null $valid_from
 * @property \DateTime|null $valid_to
 * @property-read array|null $price
 * @property-read array|null $price_with_discount
 * @property-read array|null $unit
 */
class OfferPrice extends Model
{
    use HasFactory, HasUuids;

    protected static function newFactory(): OfferPriceFactory
    {
        return new OfferPriceFactory;
    }

    protected $appends = [
        'price',
        'price_with_discount',
        'unit',
    ];

    protected $fillable = [
        'id',
        'offer_id',
        'price_type_id',
        'price_amount',
        'price_currency',
        'price_with_discount_amount',
        'price_with_discount_currency',
        'discount_percent',
        'min_quantity',
        'unit_code',
        'unit_short_name',
        'unit_full_name',
        'unit_international',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'price_amount' => 'decimal:2',
        'price_currency' => CurrencyEnum::class,
        'price_with_discount_amount' => 'decimal:2',
        'price_with_discount_currency' => CurrencyEnum::class,
        'discount_percent' => 'decimal:2',
        'min_quantity' => 'decimal:3',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function priceType(): BelongsTo
    {
        return $this->belongsTo(PriceType::class);
    }

    public function getPriceAttribute(): array
    {
        return [
            'amount' => (string) $this->price_amount,
            'currency' => $this->price_currency?->value,
        ];
    }

    public function getPriceWithDiscountAttribute(): ?array
    {
        if ($this->price_with_discount_amount === null && $this->price_with_discount_currency === null) {
            return null;
        }

        return [
            'amount' => (string) $this->price_with_discount_amount,
            'currency' => $this->price_with_discount_currency?->value,
        ];
    }

    public function getUnitAttribute(): ?array
    {
        if ($this->unit_code === null && $this->unit_short_name === null) {
            return null;
        }

        return [
            'code' => $this->unit_code,
            'short_name' => $this->unit_short_name,
            'full_name' => $this->unit_full_name,
            'international' => $this->unit_international,
        ];
    }
}
