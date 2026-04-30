<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends CommerceJsonFactory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        return [
            'id' => null,
            'product_id' => ProductFactory::new(),
            'url' => fake()->imageUrl(),
            'sort' => 0,
            'alt' => null,
            'is_main' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Изображение для конкретного товара
     */
    public function forProduct(?Product $product = null): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product?->id ?? ProductFactory::new()->create()->id,
        ]);
    }

    /**
     * Главное изображение
     */
    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_main' => true,
            'sort' => 0,
        ]);
    }

    /**
     * Дополнительное изображение
     */
    public function additional(int $sort = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'is_main' => false,
            'sort' => $sort,
        ]);
    }

    /**
     * Изображение с ALT текстом
     */
    public function withAlt(?string $alt = null): static
    {
        return $this->state(fn (array $attributes) => [
            'alt' => $alt ?? fake()->sentence(5),
        ]);
    }
}
