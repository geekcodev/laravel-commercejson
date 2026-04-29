<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Models\Offer;
use GeekCo\CommerceJson\Models\Stock;
use GeekCo\CommerceJson\Models\Warehouse;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends CommerceJsonFactory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'id' => null,
            'offer_id' => OfferFactory::new(),
            'warehouse_id' => WarehouseFactory::new(),
            'quantity' => static::quantity(),
            'quantity_reserved' => 0.000,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Остаток для конкретного предложения
     */
    public function forOffer(?Offer $offer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'offer_id' => $offer?->id ?? OfferFactory::new()->create()->id,
        ]);
    }

    /**
     * Остаток на конкретном складе
     */
    public function forWarehouse(?Warehouse $warehouse = null): static
    {
        return $this->state(fn (array $attributes) => [
            'warehouse_id' => $warehouse?->id ?? WarehouseFactory::new()->create()->id,
        ]);
    }

    /**
     * Товар в наличии
     */
    public function inStock(float $quantity = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'quantity_reserved' => 0,
        ]);
    }

    /**
     * Товар отсутствует
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'quantity_reserved' => 0,
        ]);
    }

    /**
     * Товар с зарезервированным количеством
     */
    public function withReserved(float $reserved = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => max($attributes['quantity'] ?? 100, $reserved + 10),
            'quantity_reserved' => $reserved,
        ]);
    }

    /**
     * Мало товара на складе
     */
    public function lowStock(float $quantity = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'quantity_reserved' => 0,
        ]);
    }
}
