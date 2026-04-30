<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends CommerceJsonFactory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(4, true);

        return [
            'id' => static::uuid(),
            'product_id' => ProductFactory::new(),
            'external_id' => static::externalId(),
            'name' => $name,
            'code' => 'VAR-'.Str::upper(Str::random(8)),
            'barcode' => static::barcode(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Вариант для конкретного товара
     */
    public function forProduct(?Product $product = null): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product?->id ?? ProductFactory::new()->create()->id,
        ]);
    }

    /**
     * Активный вариант
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Неактивный вариант
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
