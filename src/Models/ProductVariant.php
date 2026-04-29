<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id',
        'external_id',
        'name',
        'code',
        'barcode',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function propertyValues(): HasMany
    {
        return $this->hasMany(PropertyValue::class, 'variant_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'variant_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }
}
