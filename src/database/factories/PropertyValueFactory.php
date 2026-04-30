<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Models\ProductVariant;
use GeekCo\CommerceJson\Models\PropertyDefinition;
use GeekCo\CommerceJson\Models\PropertyValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyValue>
 */
class PropertyValueFactory extends CommerceJsonFactory
{
    protected $model = PropertyValue::class;

    public function definition(): array
    {
        return [
            'id' => static::uuid(),
            'property_id' => PropertyDefinitionFactory::new(),
            'product_id' => ProductFactory::new(),
            'variant_id' => null,
            'value_string' => null,
            'value_number' => null,
            'value_boolean' => null,
            'value_json' => null,
        ];
    }

    /**
     * Значение для свойства
     */
    public function forProperty(?PropertyDefinition $property = null): static
    {
        return $this->state(fn (array $attributes) => [
            'property_id' => $property?->id ?? PropertyDefinitionFactory::new()->create()->id,
        ]);
    }

    /**
     * Значение для товара
     */
    public function forProduct(?Product $product = null): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product?->id ?? ProductFactory::new()->create()->id,
            'variant_id' => null,
        ]);
    }

    /**
     * Значение для варианта
     */
    public function forVariant(?ProductVariant $variant = null): static
    {
        return $this->state(fn (array $attributes) => [
            'variant_id' => $variant?->id ?? ProductVariantFactory::new()->create()->id,
            'product_id' => null,
        ]);
    }

    /**
     * Строковое значение
     */
    public function stringValue(string $value): static
    {
        return $this->state(fn (array $attributes) => [
            'value_string' => $value,
        ]);
    }

    /**
     * Числовое значение
     */
    public function numberValue(float $value): static
    {
        return $this->state(fn (array $attributes) => [
            'value_number' => $value,
        ]);
    }

    /**
     * Булево значение
     */
    public function booleanValue(bool $value): static
    {
        return $this->state(fn (array $attributes) => [
            'value_boolean' => $value,
        ]);
    }

    /**
     * JSON значение (multiselect)
     */
    public function jsonValue(array $values): static
    {
        return $this->state(fn (array $attributes) => [
            'value_json' => $values,
        ]);
    }
}
