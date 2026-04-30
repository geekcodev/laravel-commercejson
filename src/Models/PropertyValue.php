<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use GeekCo\CommerceJson\Database\Factories\PropertyValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyValue extends Model
{
    use HasFactory;

    protected static function newFactory(): PropertyValueFactory
    {
        return new PropertyValueFactory;
    }

    public $timestamps = false;

    protected $fillable = [
        'property_id',
        'product_id',
        'variant_id',
        'value_string',
        'value_number',
        'value_boolean',
        'value_json',
    ];

    protected $casts = [
        'value_number' => 'decimal:4',
        'value_boolean' => 'boolean',
        'value_json' => 'array',
    ];

    public function propertyDefinition(): BelongsTo
    {
        return $this->belongsTo(PropertyDefinition::class, 'property_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
