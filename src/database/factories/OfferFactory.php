<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Models\Offer;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends CommerceJsonFactory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        return [
            'id' => static::uuid(),
            'product_id' => ProductFactory::new(),
            'variant_id' => null,
            'created_at' => null,
            'updated_at' => now(),
            'deleted_at' => null,
        ];
    }

    /**
     * Предложение для конкретного товара
     */
    public function forProduct(?Product $product = null): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product?->id ?? ProductFactory::new()->create()->id,
        ]);
    }

    /**
     * Предложение для варианта товара
     */
    public function forVariant(?ProductVariant $variant = null): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $variant?->product_id ?? ProductFactory::new()->create()->id,
            'variant_id' => $variant?->id ?? ProductVariantFactory::new()->create()->id,
        ]);
    }

    /**
     * Удалённое предложение
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
