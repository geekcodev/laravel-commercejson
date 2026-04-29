<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Models\Offer;
use GeekCo\CommerceJson\Models\OfferPrice;
use GeekCo\CommerceJson\Models\PriceType;

/**
 * @extends Factory<OfferPrice>
 */
class OfferPriceFactory extends CommerceJsonFactory
{
    protected $model = OfferPrice::class;

    public function definition(): array
    {
        return [
            'id' => null,
            'offer_id' => OfferFactory::new(),
            'price_type_id' => PriceTypeFactory::new(),
            'price_amount' => static::amount(2),
            'price_currency' => CurrencyEnum::RUB->value,
            'price_with_discount_amount' => null,
            'price_with_discount_currency' => null,
            'discount_percent' => null,
            'min_quantity' => 1.000,
            'unit_code' => '796',
            'unit_short_name' => 'шт',
            'unit_full_name' => 'штука',
            'unit_international' => 'PCE',
            'valid_from' => null,
            'valid_to' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Цена для конкретного предложения
     */
    public function forOffer(?Offer $offer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'offer_id' => $offer?->id ?? OfferFactory::new()->create()->id,
        ]);
    }

    /**
     * Цена с типом цены
     */
    public function forPriceType(?PriceType $priceType = null): static
    {
        return $this->state(fn (array $attributes) => [
            'price_type_id' => $priceType?->id ?? PriceTypeFactory::new()->create()->id,
        ]);
    }

    /**
     * Розничная цена
     */
    public function retail(float $amount = 1000): static
    {
        return $this->state(fn (array $attributes) => [
            'price_amount' => $amount,
            'price_type_id' => PriceTypeFactory::new()->default()->create()->id,
        ]);
    }

    /**
     * Оптовая цена
     */
    public function wholesale(float $amount = 800, float $minQuantity = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'price_amount' => $amount,
            'price_with_discount_amount' => $amount * 0.9,
            'discount_percent' => 10.00,
            'min_quantity' => $minQuantity,
        ]);
    }

    /**
     * Цена со скидкой
     */
    public function withDiscount(float $discountPercent = 15): static
    {
        return $this->state(fn (array $attributes) => [
            'price_with_discount_amount' => $attributes['price_amount'] * (1 - $discountPercent / 100),
            'discount_percent' => $discountPercent,
        ]);
    }

    /**
     * Цена с периодом действия
     */
    public function withValidityPeriod(?\DateTime $from = null, ?\DateTime $to = null): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => $from ?? now(),
            'valid_to' => $to ?? now()->addMonth(),
        ]);
    }

    /**
     * Цена в валюте
     */
    public function inCurrency(string $currency = 'USD'): static
    {
        return $this->state(fn (array $attributes) => [
            'price_currency' => $currency,
            'price_with_discount_currency' => $currency,
        ]);
    }
}
