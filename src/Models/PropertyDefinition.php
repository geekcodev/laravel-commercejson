<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use GeekCo\CommerceJson\Enums\PropertyTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyDefinition extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
        'type',
        'unit',
        'is_filterable',
        'is_required',
        'use_for_catalog',
        'use_for_offers',
        'use_for_documents',
        'enum_values',
        'applies_to_all',
        'category_ids',
    ];

    protected $casts = [
        'name' => 'array', // JSON: string или {ru: "...", en: "..."}
        'type' => PropertyTypeEnum::class,
        'is_filterable' => 'boolean',
        'is_required' => 'boolean',
        'use_for_catalog' => 'boolean',
        'use_for_offers' => 'boolean',
        'use_for_documents' => 'boolean',
        'enum_values' => 'array',
        'applies_to_all' => 'boolean',
        'category_ids' => 'array',
    ];

    public function propertyValues(): HasMany
    {
        return $this->hasMany(PropertyValue::class, 'property_id');
    }
}
