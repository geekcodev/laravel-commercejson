<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Seeders;

use GeekCo\CommerceJson\Models\BankAccount;
use GeekCo\CommerceJson\Models\Category;
use GeekCo\CommerceJson\Models\Contact;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\Offer;
use GeekCo\CommerceJson\Models\OfferPrice;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\OrderItem;
use GeekCo\CommerceJson\Models\PriceType;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Models\ProductImage;
use GeekCo\CommerceJson\Models\ProductVariant;
use GeekCo\CommerceJson\Models\PropertyDefinition;
use GeekCo\CommerceJson\Models\PropertyValue;
use GeekCo\CommerceJson\Models\Stock;
use GeekCo\CommerceJson\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Тестовый сидер для создания полной тестовой базы данных
 */
class TestDatabaseSeeder extends Seeder
{
    /**
     * Seed the test database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting test database seeding...');

        // Создаём справочники
        $priceType = PriceType::factory()->default()->create();
        $warehouse = Warehouse::factory()->default()->create();

        // Создаём категории
        $categories = collect();
        for ($i = 0; $i < 5; $i++) {
            $categories->push(Category::factory()->create());
        }
        $parentCategory = $categories->first();
        $childCategories = collect();
        for ($i = 0; $i < 3; $i++) {
            $childCategories->push(Category::factory()->create(['parent_id' => $parentCategory->id]));
        }

        // Создаём контрагентов
        $supplier = Counterparty::factory()->legalEntity()->create(['name' => 'ООО "Поставщик"']);
        $customer = Counterparty::factory()->legalEntity()->create(['name' => 'ООО "Клиент"']);

        // Добавляем контакты контрагентам
        for ($i = 0; $i < 3; $i++) {
            Contact::factory()->forCounterparty($supplier)->create();
        }
        for ($i = 0; $i < 2; $i++) {
            Contact::factory()->forCounterparty($customer)->create();
        }

        // Добавляем банковские счета
        for ($i = 0; $i < 2; $i++) {
            BankAccount::factory()->forCounterparty($supplier)->create();
        }

        // Создаём свойства
        $properties = collect();
        for ($i = 0; $i < 5; $i++) {
            $properties->push(PropertyDefinition::factory()->create());
        }

        // Создаём товары
        $products = collect();
        for ($i = 0; $i < 20; $i++) {
            $products->push(Product::factory()
                ->forCategory($categories->random())
                ->create());
        }

        // Создаём варианты товаров
        foreach ($products->take(10) as $product) {
            for ($i = 0; $i < 3; $i++) {
                ProductVariant::factory()->forProduct($product)->create();
            }
        }

        // Создаём изображения товаров
        foreach ($products as $product) {
            ProductImage::factory()->main()->forProduct($product)->create();
            for ($i = 0; $i < 3; $i++) {
                ProductImage::factory()->additional()->forProduct($product)->create();
            }
        }

        // Создаём предложения для товаров
        foreach ($products as $product) {
            $offer = Offer::factory()->forProduct($product)->create();

            // Создаём цены для разных типов цен
            OfferPrice::factory()
                ->retail(fake()->randomFloat(2, 100, 10000))
                ->forOffer($offer)
                ->forPriceType($priceType)
                ->create();

            OfferPrice::factory()
                ->wholesale(fake()->randomFloat(2, 50, 5000), 10)
                ->forOffer($offer)
                ->create();

            // Создаём остатки на складах
            Stock::factory()
                ->forOffer($offer)
                ->forWarehouse($warehouse)
                ->inStock(fake()->randomFloat(3, 0, 500))
                ->create();
        }

        // Создаём значения свойств для товаров
        // Для каждого товара создаём уникальные значения свойств (без дубликатов product_id + property_id)
        $productsForProperties = $products->take(10);
        foreach ($productsForProperties as $product) {
            // Выбираем 3 случайных уникальных свойства для этого товара
            $productProperties = $properties->random(3);
            foreach ($productProperties as $property) {
                PropertyValue::factory()
                    ->forProperty($property)
                    ->forProduct($product)
                    ->create();
            }
        }

        // Создаём заказы (цикл для избежания проблем с PostgreSQL и лимитом параметров)
        $orders = collect();
        for ($i = 0; $i < 30; $i++) {
            $orders->push(Order::factory()
                ->forCounterparty($customer)
                ->fromWarehouse($warehouse)
                ->create());
        }

        // Создаём позиции заказов
        foreach ($orders as $order) {
            $orderProducts = $products->random(fake()->numberBetween(1, 5));

            foreach ($orderProducts as $product) {
                OrderItem::factory()
                    ->forOrder($order)
                    ->forProduct($product)
                    ->withQuantity(fake()->randomFloat(3, 1, 10))
                    ->withPrice(fake()->randomFloat(2, 100, 50000))
                    ->create();
            }

            // Пересчитываем суммы заказа
            $this->recalculateOrderTotals($order);
        }

        $this->command->info('✅ Test database seeding completed!');
        $this->command->info(sprintf(
            '   Created: %d categories, %d products, %d orders, %d counterparties',
            Category::count(),
            Product::count(),
            Order::count(),
            Counterparty::count()
        ));
    }

    /**
     * Пересчитать суммы заказа
     */
    protected function recalculateOrderTotals(Order $order): void
    {
        $subtotal = $order->items()->sum('total_amount');
        $delivery = $order->delivery_cost_amount ?? 0;
        $tax = $subtotal * 0.20; // НДС 20%
        $total = $subtotal + $delivery + $tax;

        $order->update([
            'totals_subtotal_amount' => $subtotal,
            'totals_delivery_amount' => $delivery,
            'totals_tax_amount' => $tax,
            'totals_total_amount' => $total,
        ]);
    }
}
