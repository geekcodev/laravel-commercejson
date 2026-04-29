<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends CommerceJsonFactory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'id' => static::uuid(),
            'parent_id' => null,
            'name' => $name,
            'code' => 'CAT-'.Str::upper(Str::random(6)),
            'sort' => fake()->numberBetween(1, 1000),
            'is_active' => true,
            'image_url' => fake()->imageUrl(),
            'seo_title' => "Купить {$name} - интернет-магазин",
            'seo_description' => fake()->sentence(20),
            'seo_keywords' => implode(', ', fake()->words(10)),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Категория с родителем
     */
    public function withParent(?Category $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? CategoryFactory::new()->create()->id,
        ]);
    }

    /**
     * Активная категория
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Неактивная категория
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Категория с SEO данными
     */
    public function withSeo(): static
    {
        return $this->state(fn (array $attributes) => [
            'seo_title' => fake()->sentence(5),
            'seo_description' => fake()->paragraph(),
            'seo_keywords' => implode(', ', fake()->words(15)),
        ]);
    }
}
