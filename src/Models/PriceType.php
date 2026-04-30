<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use GeekCo\CommerceJson\Database\Factories\PriceTypeFactory;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceType extends Model
{
    use HasFactory, HasUuids;

    protected static function newFactory(): PriceTypeFactory
    {
        return new PriceTypeFactory;
    }

    protected $fillable = [
        'name',
        'currency',
        'description',
        'is_default',
    ];

    protected $casts = [
        'currency' => CurrencyEnum::class,
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function offerPrices(): HasMany
    {
        return $this->hasMany(OfferPrice::class);
    }

    public function counterparties(): HasMany
    {
        return $this->hasMany(Counterparty::class);
    }
}
