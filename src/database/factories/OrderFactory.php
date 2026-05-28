<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends CommerceJsonFactory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'id' => static::uuid(),
            'number' => 'ORD-'.date('Ymd').'-'.Str::upper(Str::random(6)),
            'external_id' => static::externalId(),
            'status' => OrderStatusEnum::New->value,
            'document_type' => DocumentTypeEnum::Order->value,
            'role' => null,
            'base_currency' => CurrencyEnum::RUB->value,
            'exchange_rate' => 1.0000,
            'payment_terms' => null,
            'counterparty_id' => CounterpartyFactory::new(),
            'warehouse_id' => WarehouseFactory::new(),
            'comment' => null,
            'customer_name' => null,
            'customer_phone' => static::generatePhone(),
            'customer_email' => static::generateEmail(),
            'customer_counterparty_id' => null,
            'delivery_type' => 'courier',
            'delivery_address_country' => 'RU',
            'delivery_address_region' => fake()->city(),
            'delivery_address_district' => null,
            'delivery_address_city' => fake()->city(),
            'delivery_address_street' => fake()->streetName(),
            'delivery_address_house' => fake()->buildingNumber(),
            'delivery_address_building' => null,
            'delivery_address_apartment' => null,
            'delivery_address_postal_code' => fake()->postcode(),
            'delivery_address_full' => null,
            'delivery_method_id' => null,
            'delivery_method_name' => 'Курьерская доставка',
            'delivery_cost_amount' => 500.00,
            'delivery_cost_currency' => CurrencyEnum::RUB->value,
            'delivery_tracking_number' => null,
            'delivery_shipped_at' => null,
            'delivery_estimated_date' => now()->addDays(3)->format('Y-m-d'),
            'payment_type' => 'card',
            'payment_status' => 'pending',
            'payment_amount' => null,
            'payment_currency' => CurrencyEnum::RUB->value,
            'payment_paid_at' => null,
            'payment_transaction_id' => null,
            'totals_subtotal_amount' => 0.00,
            'totals_subtotal_currency' => CurrencyEnum::RUB->value,
            'totals_discount_amount' => 0.00,
            'totals_discount_currency' => CurrencyEnum::RUB->value,
            'totals_delivery_amount' => 500.00,
            'totals_delivery_currency' => CurrencyEnum::RUB->value,
            'totals_tax_amount' => 0.00,
            'totals_tax_currency' => CurrencyEnum::RUB->value,
            'totals_total_amount' => 500.00,
            'totals_total_currency' => CurrencyEnum::RUB->value,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ];
    }

    /**
     * Заказ для конкретного контрагента
     */
    public function forCounterparty(?Counterparty $counterparty = null): static
    {
        return $this->state(fn (array $attributes) => [
            'counterparty_id' => $counterparty?->id ?? CounterpartyFactory::new()->create()->id,
        ]);
    }

    /**
     * Заказ со склада
     */
    public function fromWarehouse(?Warehouse $warehouse = null): static
    {
        return $this->state(fn (array $attributes) => [
            'warehouse_id' => $warehouse?->id ?? WarehouseFactory::new()->create()->id,
        ]);
    }

    /**
     * Новый заказ
     */
    public function asNew(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatusEnum::New->value,
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Подтверждённый заказ
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatusEnum::Confirmed->value,
        ]);
    }

    /**
     * Заказ в обработке
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatusEnum::Processing->value,
        ]);
    }

    /**
     * Отгруженный заказ
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatusEnum::Shipped->value,
            'delivery_shipped_at' => now(),
        ]);
    }

    /**
     * Доставленный заказ
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatusEnum::Delivered->value,
            'payment_status' => 'paid',
            'payment_paid_at' => now(),
        ]);
    }

    /**
     * Отменённый заказ
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatusEnum::Cancelled->value,
        ]);
    }

    /**
     * Оплаченный заказ
     */
    public function paid(?float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'payment_amount' => $amount ?? $attributes['totals_total_amount'],
            'payment_paid_at' => now(),
        ]);
    }

    /**
     * Заказ с доставкой
     */
    public function withDelivery(float $cost = 500): static
    {
        return $this->state(fn (array $attributes) => [
            'delivery_cost_amount' => $cost,
            'delivery_type' => 'courier',
        ]);
    }

    /**
     * Заказ с самовывозом
     */
    public function withPickup(): static
    {
        return $this->state(fn (array $attributes) => [
            'delivery_type' => 'pickup',
            'delivery_cost_amount' => 0,
            'totals_delivery_amount' => 0,
        ]);
    }

    /**
     * Удалённый заказ
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
