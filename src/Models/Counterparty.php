<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Models;

use GeekCo\CommerceJson\Database\Factories\CounterpartyFactory;
use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Counterparty extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected const string MODEL_FACTORY_CLASS = CounterpartyFactory::class;

    protected $fillable = [
        'external_id',
        'type',
        'name',
        'short_name',
        'inn',
        'kpp',
        'ogrn',
        'okved',
        'okpo',
        'okopf',
        'okfs',
        'registration_date',
        'legal_address_country',
        'legal_address_region',
        'legal_address_district',
        'legal_address_city',
        'legal_address_street',
        'legal_address_house',
        'legal_address_building',
        'legal_address_apartment',
        'legal_address_postal_code',
        'legal_address_full',
        'actual_address_country',
        'actual_address_region',
        'actual_address_district',
        'actual_address_city',
        'actual_address_street',
        'actual_address_house',
        'actual_address_building',
        'actual_address_apartment',
        'actual_address_postal_code',
        'actual_address_full',
        'price_type_id',
        'credit_limit_amount',
        'credit_limit_currency',
        'is_active',
    ];

    protected $casts = [
        'type' => CounterpartyTypeEnum::class,
        'registration_date' => 'date',
        'credit_limit_amount' => 'decimal:2',
        'credit_limit_currency' => CurrencyEnum::class,
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function priceType(): BelongsTo
    {
        return $this->belongsTo(PriceType::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function representatives(): HasMany
    {
        return $this->hasMany(Representative::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function ordersAsCustomer(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_counterparty_id');
    }

    public function productsAsManufacturer(): HasMany
    {
        return $this->hasMany(Product::class, 'manufacturer_id');
    }

    public function productsAsBrandOwner(): HasMany
    {
        return $this->hasMany(Product::class, 'manufacturer_brand_owner_id');
    }

    public function customAttributes(): MorphMany
    {
        return $this->morphMany(CustomAttribute::class, 'attributable');
    }
}
