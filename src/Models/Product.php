<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use GeekCo\CommerceJson\Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static function newFactory(): ProductFactory
    {
        return new ProductFactory;
    }

    protected $fillable = [
        'external_id',
        'name',
        'code',
        'barcode',
        'category_id',
        'description',
        'short_description',
        'tax_rate',
        'weight',
        'dimensions_length',
        'dimensions_width',
        'dimensions_height',
        'manufacturer_country',
        'manufacturer_brand',
        'manufacturer_brand_owner_id',
        'manufacturer_id',
        'unit_code',
        'unit_short_name',
        'unit_full_name',
        'unit_international',
        'is_active',
        'seo_title',
        'seo_description',
        'seo_keywords',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2',
        'weight' => 'decimal:3',
        'dimensions_length' => 'decimal:2',
        'dimensions_width' => 'decimal:2',
        'dimensions_height' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'manufacturer_id');
    }

    public function manufacturerBrandOwner(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'manufacturer_brand_owner_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function propertyValues(): HasMany
    {
        return $this->hasMany(PropertyValue::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customAttributes(): MorphMany
    {
        return $this->morphMany(CustomAttribute::class, 'attributable');
    }

    public function analogues(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_analogues', 'product_id', 'analogue_id')
            ->withTimestamps();
    }

    public function analogFor(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_analogues', 'analogue_id', 'product_id')
            ->withTimestamps();
    }

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_components', 'product_id', 'component_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function componentFor(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_components', 'component_id', 'product_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
