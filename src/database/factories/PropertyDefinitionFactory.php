<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Enums\PropertyTypeEnum;
use GeekCo\CommerceJson\Models\PropertyDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyDefinition>
 */
class PropertyDefinitionFactory extends CommerceJsonFactory
{
    protected $model = PropertyDefinition::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'id' => static::uuid(),
            'name' => static::localizedString($name),
            'code' => 'PROP-'.Str::upper(Str::random(6)),
            'type' => PropertyTypeEnum::String->value,
            'unit' => null,
            'is_filterable' => false,
            'is_required' => false,
            'use_for_catalog' => true,
            'use_for_offers' => false,
            'use_for_documents' => false,
            'enum_values' => null,
            'applies_to_all' => false,
            'category_ids' => null,
        ];
    }

    /**
     * Строковое свойство
     */
    public function stringType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PropertyTypeEnum::String->value,
        ]);
    }

    /**
     * Числовое свойство
     */
    public function numberType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PropertyTypeEnum::Number->value,
            'unit' => 'шт',
        ]);
    }

    /**
     * Булево свойство
     */
    public function booleanType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PropertyTypeEnum::Boolean->value,
        ]);
    }

    /**
     * Свойство-перечисление
     */
    public function enumType(array $values = ['Красный', 'Зелёный', 'Синий']): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PropertyTypeEnum::Enum->value,
            'enum_values' => array_map(fn ($v) => ['id' => static::uuid(), 'value' => $v], $values),
        ]);
    }

    /**
     * Фильтруемое свойство
     */
    public function filterable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_filterable' => true,
        ]);
    }

    /**
     * Обязательное свойство
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    /**
     * Свойство для каталога
     */
    public function forCatalog(): static
    {
        return $this->state(fn (array $attributes) => [
            'use_for_catalog' => true,
        ]);
    }

    /**
     * Свойство для предложений
     */
    public function forOffers(): static
    {
        return $this->state(fn (array $attributes) => [
            'use_for_offers' => true,
        ]);
    }
}
