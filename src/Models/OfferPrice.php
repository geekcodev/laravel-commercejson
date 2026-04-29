<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferPrice extends Model
{
    use HasFactory;

    protected $fillable = [
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
}
