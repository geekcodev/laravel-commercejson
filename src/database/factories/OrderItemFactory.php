<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\OrderItem;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends CommerceJsonFactory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $quantity = (float) static::quantity();
        $price = (float) static::amount(2);
        $total = $quantity * $price;

        return [
            'id' => static::uuid(),
            'order_id' => OrderFactory::new(),
            'product_id' => ProductFactory::new(),
            'variant_id' => null,
            'warehouse_id' => null,
            'product_name' => fake()->words(3, true),
            'product_code' => 'PRD-'.Str::upper(Str::random(8)),
            'quantity' => $quantity,
            'unit_code' => '796',
            'unit_short_name' => 'шт',
            'unit_full_name' => 'штука',
            'unit_international' => 'PCE',
            'price_amount' => $price,
            'price_currency' => CurrencyEnum::RUB->value,
            'discount_amount' => null,
            'discount_currency' => null,
            'total_amount' => $total,
            'total_currency' => CurrencyEnum::RUB->value,
            'country_of_origin' => 'RU',
            'customs_declaration_number' => null,
            'tax_rate' => 20.00,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Позиция для конкретного заказа
     */
    public function forOrder(?Order $order = null): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order?->id ?? OrderFactory::new()->create()->id,
        ]);
    }

    /**
     * Позиция для конкретного товара
     */
    public function forProduct(?Product $product = null): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product?->id ?? ProductFactory::new()->create()->id,
            'product_name' => $product?->name ?? $attributes['product_name'],
            'product_code' => $product?->code ?? $attributes['product_code'],
        ]);
    }

    /**
     * Позиция для варианта товара
     */
    public function forVariant(?ProductVariant $variant = null): static
    {
        return $this->state(fn (array $attributes) => [
            'variant_id' => $variant?->id,
            'product_id' => $variant?->product_id ?? ProductFactory::new()->create()->id,
        ]);
    }

    /**
     * Позиция с количеством
     */
    public function withQuantity(float $quantity = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'total_amount' => $quantity * ($attributes['price_amount'] ?? 100),
        ]);
    }

    /**
     * Позиция с ценой
     */
    public function withPrice(float $price = 1000): static
    {
        return $this->state(fn (array $attributes) => [
            'price_amount' => $price,
            'total_amount' => $price * ($attributes['quantity'] ?? 1),
        ]);
    }

    /**
     * Позиция со скидкой
     */
    public function withDiscount(float $discountAmount = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_amount' => $discountAmount,
            'discount_currency' => CurrencyEnum::RUB->value,
            'total_amount' => $attributes['total_amount'] - $discountAmount,
        ]);
    }

    /**
     * Позиция с НДС 20%
     */
    public function withVat20(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_rate' => 20.00,
        ]);
    }

    /**
     * Позиция без НДС
     */
    public function withoutVat(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_rate' => 0.00,
        ]);
    }
}
