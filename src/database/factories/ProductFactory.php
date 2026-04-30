<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Models\Category;
use GeekCo\CommerceJson\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends CommerceJsonFactory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'id' => static::uuid(),
            'external_id' => static::externalId(),
            'name' => $name,
            'code' => 'PRD-'.Str::upper(Str::random(8)),
            'barcode' => static::barcode(),
            'category_id' => CategoryFactory::new(),
            'description' => fake()->paragraph(3),
            'short_description' => fake()->sentence(10),
            'tax_rate' => 20.00,
            'weight' => fake()->randomFloat(3, 0.01, 100),
            'dimensions_length' => fake()->randomFloat(2, 1, 200),
            'dimensions_width' => fake()->randomFloat(2, 1, 200),
            'dimensions_height' => fake()->randomFloat(2, 1, 200),
            'manufacturer_country' => 'RU',
            'manufacturer_brand' => fake()->company(),
            'manufacturer_brand_owner_id' => null,
            'manufacturer_id' => null,
            'unit_code' => '796',
            'unit_short_name' => 'шт',
            'unit_full_name' => 'штука',
            'unit_international' => 'PCE',
            'is_active' => true,
            'seo_title' => "Купить {$name} - цена, характеристики",
            'seo_description' => fake()->sentence(20),
            'seo_keywords' => implode(', ', fake()->words(10)),
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ];
    }

    /**
     * Товар с конкретной категорией
     */
    public function forCategory(?Category $category = null): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category?->id ?? CategoryFactory::new()->create()->id,
        ]);
    }

    /**
     * Активный товар
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Неактивный товар
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Удалённый товар
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }

    /**
     * Товар с производителем
     */
    public function withManufacturer(): static
    {
        return $this->state(fn (array $attributes) => [
            'manufacturer_id' => CounterpartyFactory::new()->create()->id,
            'manufacturer_brand_owner_id' => CounterpartyFactory::new()->create()->id,
        ]);
    }

    /**
     * Товар с НДС 20%
     */
    public function withVat20(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_rate' => 20.00,
        ]);
    }

    /**
     * Товар без НДС
     */
    public function withoutVat(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_rate' => 0.00,
        ]);
    }

    /**
     * Товар с габаритами
     */
    public function withDimensions(float $length = 10, float $width = 5, float $height = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'dimensions_length' => $length,
            'dimensions_width' => $width,
            'dimensions_height' => $height,
        ]);
    }

    /**
     * Товар с весом
     */
    public function withWeight(float $weight = 1.5): static
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $weight,
        ]);
    }
}
