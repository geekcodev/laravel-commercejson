<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use GeekCo\CommerceJson\Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static function newFactory(): WarehouseFactory
    {
        return new WarehouseFactory;
    }

    protected $fillable = [
        'id',
        'external_id',
        'name',
        'code',
        'address_country',
        'address_region',
        'address_district',
        'address_city',
        'address_street',
        'address_house',
        'address_building',
        'address_apartment',
        'address_postal_code',
        'address_full',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
