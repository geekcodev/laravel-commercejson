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
use Illuminate\Support\Facades\DB;

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

        // Создаём справочники: все типы цен и все склады
        $priceTypeDefault = PriceType::factory()->default()->create();
        $priceTypeWholesale = PriceType::factory()->wholesale()->create();
        $priceTypeDealer = PriceType::factory()->dealer()->create();
        $priceTypeVip = PriceType::factory()->create(['name' => 'VIP цена', 'description' => 'Цены для VIP клиентов']);
        $priceTypes = [$priceTypeDefault, $priceTypeWholesale, $priceTypeDealer, $priceTypeVip];

        $warehouseMain = Warehouse::factory()->default()->create();
        $warehouseSpb = Warehouse::factory()->create([
            'name' => 'Склад Северо-Запад',
            'code' => 'WH-SPB-TEST',
        ]);
        $warehouseSouth = Warehouse::factory()->create([
            'name' => 'Склад Южный',
            'code' => 'WH-SOUTH-TEST',
        ]);
        $warehouses = [$warehouseMain, $warehouseSpb, $warehouseSouth];

        // Создаём категории автозапчастей
        $categories = collect();
        for ($i = 0; $i < 5; $i++) {
            $categories->push(Category::factory()->create());
        }
        $parentCategory = $categories->first();
        $childCategories = collect();
        for ($i = 0; $i < 3; $i++) {
            $childCategories->push(Category::factory()->create(['parent_id' => $parentCategory->id]));
        }

        // Создаём поставщиков и покупателей
        $supplier = Counterparty::factory()->legalEntity()->create(['name' => 'ООО "АвтоДистрибьюция"']);
        $customer = Counterparty::factory()->legalEntity()->create(['name' => 'ООО "Автосервис Плюс"']);

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

        // Создаём товары (автозапчасти)
        $products = collect();
        $productNames = [
            'Масло моторное 5W-30 4л',
            'Колодки тормозные передние',
            'Фильтр масляный',
            'Свечи зажигания комплект',
            'Ремень ГРМ',
            'Фильтр воздушный',
            'Амортизатор передний',
            'Диск тормозной',
            'Радиатор охлаждения',
            'Комплект сцепления',
            'Датчик кислорода',
            'Помпа водяная',
            'Термостат',
            'Глушитель задний',
            'Фара головная',
            'Стартер',
            'Генератор',
            'Подшипник ступицы',
            'Стойка амортизатора',
            'Фильтр топливный',
        ];

        foreach ($productNames as $name) {
            $products->push(Product::factory()
                ->forCategory($categories->random())
                ->create(['name' => $name]));
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

        // Создаём предложения для товаров с ценами для ВСЕХ типов цен
        // и остатками на ВСЕХ складах
        foreach ($products as $product) {
            $offer = Offer::factory()->forProduct($product)->create();

            foreach ($priceTypes as $index => $pt) {
                $basePrice = fake()->randomFloat(2, 500, 50000);
                $coeff = match ($index) {
                    0 => 1.0,
                    1 => 0.82,
                    2 => 0.72,
                    3 => 0.62,
                    default => 1.0,
                };
                $amount = round($basePrice * $coeff, 2);

                OfferPrice::factory()
                    ->retail($amount)
                    ->forOffer($offer)
                    ->forPriceType($pt)
                    ->create([
                        'min_quantity' => $index >= 2 ? ($index === 3 ? 20 : 10) : 1,
                    ]);
            }

            foreach ($warehouses as $warehouse) {
                Stock::factory()
                    ->forOffer($offer)
                    ->forWarehouse($warehouse)
                    ->inStock(fake()->randomFloat(3, 0, 500))
                    ->create();
            }
        }

        // Аналоги: каждому товару назначаем 5-7 случайных аналогов
        $allProductIds = $products->pluck('id')->all();
        $analogueBuffer = [];
        $now = now();

        foreach ($allProductIds as $productId) {
            $count = fake()->numberBetween(5, 7);
            $candidates = array_values(array_diff($allProductIds, [$productId]));
            shuffle($candidates);
            $selected = array_slice($candidates, 0, min($count, count($candidates)));

            foreach ($selected as $analogueId) {
                $analogueBuffer[] = [
                    'product_id' => $productId,
                    'analogue_id' => $analogueId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($analogueBuffer)) {
            foreach (array_chunk($analogueBuffer, 2000) as $chunk) {
                DB::table('product_analogues')->insert($chunk);
            }
        }

        // Создаём значения свойств для товаров
        $productsForProperties = $products->take(10);
        foreach ($productsForProperties as $product) {
            $productProperties = $properties->random(3);
            foreach ($productProperties as $property) {
                PropertyValue::factory()
                    ->forProperty($property)
                    ->forProduct($product)
                    ->create();
            }
        }

        // Создаём заказы
        $orders = collect();
        for ($i = 0; $i < 30; $i++) {
            $orders->push(Order::factory()
                ->forCounterparty($customer)
                ->fromWarehouse($warehouseMain)
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
                    ->withPrice(fake()->randomFloat(2, 500, 50000))
                    ->create();
            }

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
