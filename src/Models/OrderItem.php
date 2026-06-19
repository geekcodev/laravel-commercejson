<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use Carbon\Carbon;
use GeekCo\CommerceJson\Database\Factories\OrderItemFactory;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $order_id
 * @property string $product_id
 * @property string|null $variant_id
 * @property string|null $product_name
 * @property string|null $product_code
 * @property string $quantity
 * @property string|null $unit_code
 * @property string|null $unit_short_name
 * @property string|null $unit_full_name
 * @property string|null $unit_international
 * @property string $price_amount
 * @property CurrencyEnum $price_currency
 * @property string|null $discount_amount
 * @property CurrencyEnum|null $discount_currency
 * @property string $total_amount
 * @property CurrencyEnum $total_currency
 * @property string|null $country_of_origin
 * @property string|null $customs_declaration_number
 * @property string|null $tax_rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<OrderItemTax> $taxes
 */
class OrderItem extends Model
{
    use HasFactory, HasUuids;

    protected static function newFactory(): OrderItemFactory
    {
        return new OrderItemFactory;
    }

    protected $fillable = [
        'id',
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'product_code',
        'quantity',
        'unit_code',
        'unit_short_name',
        'unit_full_name',
        'unit_international',
        'price_amount',
        'price_currency',
        'discount_amount',
        'discount_currency',
        'total_amount',
        'total_currency',
        'country_of_origin',
        'customs_declaration_number',
        'tax_rate',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'price_amount' => 'decimal:2',
        'price_currency' => CurrencyEnum::class,
        'discount_amount' => 'decimal:2',
        'discount_currency' => CurrencyEnum::class,
        'total_amount' => 'decimal:2',
        'total_currency' => CurrencyEnum::class,
        'tax_rate' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(OrderItemTax::class);
    }
}
