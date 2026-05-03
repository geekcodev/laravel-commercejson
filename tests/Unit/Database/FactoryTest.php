<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Database\Factories;

use GeekCo\CommerceJson\Database\Factories\CategoryFactory;
use GeekCo\CommerceJson\Database\Factories\CounterpartyFactory;
use GeekCo\CommerceJson\Database\Factories\OfferFactory;
use GeekCo\CommerceJson\Database\Factories\OrderFactory;
use GeekCo\CommerceJson\Database\Factories\OrderItemFactory;
use GeekCo\CommerceJson\Database\Factories\ProductFactory;
use GeekCo\CommerceJson\Database\Factories\StockFactory;
use GeekCo\CommerceJson\Database\Factories\WarehouseFactory;
use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Enums\PaymentStatusEnum;
use GeekCo\CommerceJson\Models\Category;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Tests\TestCase;

/**
 * Тесты для Factory классов
 *
 * @covers \GeekCo\CommerceJson\Database\Factories\ProductFactory
 * @covers \GeekCo\CommerceJson\Database\Factories\OrderFactory
 */
class FactoryTest extends TestCase
{
    /**
     * @test
     */
    public function product_factory_creates_valid_product(): void
    {
        $product = ProductFactory::new()->create();

        $this->assertInstanceOf(Product::class, $product);
        $this->assertValidUuid($product->id);
        $this->assertNotNull($product->name);
        $this->assertNotNull($product->code);
        $this->assertNotNull($product->category_id);
        $this->assertTrue($product->is_active);
    }

    /**
     * @test
     */
    public function product_factory_with_active_state(): void
    {
        $product = ProductFactory::new()->active()->create();

        $this->assertTrue($product->is_active);
    }

    /**
     * @test
     */
    public function product_factory_with_inactive_state(): void
    {
        $product = ProductFactory::new()->inactive()->create();

        $this->assertFalse($product->is_active);
    }

    /**
     * @test
     */
    public function product_factory_with_vat_20(): void
    {
        $product = ProductFactory::new()->withVat20()->create();

        $this->assertEquals(20.00, $product->tax_rate);
    }

    /**
     * @test
     */
    public function product_factory_without_vat(): void
    {
        $product = ProductFactory::new()->withoutVat()->create();

        $this->assertEquals(0.00, $product->tax_rate);
    }

    /**
     * @test
     */
    public function product_factory_with_dimensions(): void
    {
        $product = ProductFactory::new()
            ->withDimensions(100, 50, 25)
            ->create();

        $this->assertEquals(100.00, $product->dimensions_length);
        $this->assertEquals(50.00, $product->dimensions_width);
        $this->assertEquals(25.00, $product->dimensions_height);
    }

    /**
     * @test
     */
    public function product_factory_with_weight(): void
    {
        $product = ProductFactory::new()
            ->withWeight(5.5)
            ->create();

        $this->assertEquals(5.5, $product->weight);
    }

    /**
     * @test
     */
    public function product_factory_with_manufacturer(): void
    {
        $product = ProductFactory::new()
            ->withManufacturer()
            ->create();

        $this->assertNotNull($product->manufacturer_id);
        $this->assertNotNull($product->manufacturer_brand_owner_id);
    }

    /**
     * @test
     */
    public function product_factory_creates_multiple(): void
    {
        $products = ProductFactory::new()->times(10)->create();

        $this->assertCount(10, $products);
        $this->assertEquals(10, Product::count());
    }

    /**
     * @test
     */
    public function category_factory_creates_valid_category(): void
    {
        $category = CategoryFactory::new()->create();

        $this->assertInstanceOf(Category::class, $category);
        $this->assertValidUuid($category->id);
        $this->assertNotNull($category->name);
        $this->assertTrue($category->is_active);
    }

    /**
     * @test
     */
    public function category_factory_with_parent(): void
    {
        $parent = CategoryFactory::new()->create();
        $child = CategoryFactory::new()->withParent($parent)->create();

        $this->assertEquals($parent->id, $child->parent_id);
    }

    /**
     * @test
     */
    public function category_factory_with_seo(): void
    {
        $category = CategoryFactory::new()->withSeo()->create();

        $this->assertNotNull($category->seo_title);
        $this->assertNotNull($category->seo_description);
        $this->assertNotNull($category->seo_keywords);
    }

    /**
     * @test
     */
    public function order_factory_creates_valid_order(): void
    {
        $order = OrderFactory::new()->create();

        $this->assertInstanceOf(Order::class, $order);
        $this->assertValidUuid($order->id);
        $this->assertNotNull($order->number);
        $this->assertEquals(OrderStatusEnum::New, $order->status);
    }

    /**
     * @test
     */
    public function order_factory_with_states(): void
    {
        $newOrder = OrderFactory::new()->asNew()->create();
        $this->assertEquals(OrderStatusEnum::New, $newOrder->status);

        $confirmedOrder = OrderFactory::new()->confirmed()->create();
        $this->assertEquals(OrderStatusEnum::Confirmed, $confirmedOrder->status);

        $processingOrder = OrderFactory::new()->processing()->create();
        $this->assertEquals(OrderStatusEnum::Processing, $processingOrder->status);

        $shippedOrder = OrderFactory::new()->shipped()->create();
        $this->assertEquals(OrderStatusEnum::Shipped, $shippedOrder->status);

        $deliveredOrder = OrderFactory::new()->delivered()->create();
        $this->assertEquals(OrderStatusEnum::Delivered, $deliveredOrder->status);

        $cancelledOrder = OrderFactory::new()->cancelled()->create();
        $this->assertEquals(OrderStatusEnum::Cancelled, $cancelledOrder->status);
    }

    /**
     * @test
     */
    public function order_factory_paid(): void
    {
        $order = OrderFactory::new()->paid(5000)->create();

        $this->assertEquals(PaymentStatusEnum::Paid, $order->payment_status);
        $this->assertEquals(5000.00, $order->payment_amount);
        $this->assertNotNull($order->payment_paid_at);
    }

    /**
     * @test
     */
    public function order_factory_with_delivery(): void
    {
        $order = OrderFactory::new()->withDelivery(500)->create();

        $this->assertEquals(500.00, $order->delivery_cost_amount);
        $this->assertEquals('courier', $order->delivery_type);
    }

    /**
     * @test
     */
    public function order_factory_with_pickup(): void
    {
        $order = OrderFactory::new()->withPickup()->create();

        $this->assertEquals('pickup', $order->delivery_type);
        $this->assertEquals(0.00, $order->delivery_cost_amount);
    }

    /**
     * @test
     */
    public function order_item_factory_with_quantity_and_price(): void
    {
        $order = OrderFactory::new()->create();
        $product = ProductFactory::new()->create();

        $orderItem = OrderItemFactory::new()
            ->forOrder($order)
            ->forProduct($product)
            ->withQuantity(5)
            ->withPrice(1000)
            ->create();

        $this->assertEquals(5.000, $orderItem->quantity);
        $this->assertEquals(1000.00, $orderItem->price_amount);
        $this->assertEquals(5000.00, $orderItem->total_amount);
    }

    /**
     * @test
     */
    public function order_item_factory_with_discount(): void
    {
        $orderItem = OrderItemFactory::new()
            ->withQuantity(2)
            ->withPrice(1000)
            ->withDiscount(200)
            ->create();

        $this->assertEquals(200.00, $orderItem->discount_amount);
        $this->assertEquals(1800.00, $orderItem->total_amount); // 2000 - 200
    }

    /**
     * @test
     */
    public function warehouse_factory_default(): void
    {
        $warehouse = WarehouseFactory::new()->default()->create();

        $this->assertTrue($warehouse->is_default);
        $this->assertEquals('WH-MAIN', $warehouse->code);
        $this->assertEquals('Основной склад', $warehouse->name);
    }

    /**
     * @test
     */
    public function counterparty_factory_legal_entity(): void
    {
        $counterparty = CounterpartyFactory::new()->legalEntity()->create();

        $this->assertEquals(CounterpartyTypeEnum::LegalEntity, $counterparty->type);
        $this->assertNotNull($counterparty->inn);
        $this->assertNotNull($counterparty->kpp);
        $this->assertEquals(10, strlen($counterparty->inn));
    }

    /**
     * @test
     */
    public function counterparty_factory_individual_entrepreneur(): void
    {
        $counterparty = CounterpartyFactory::new()->individualEntrepreneur()->create();

        $this->assertEquals(CounterpartyTypeEnum::IndividualEntrepreneur, $counterparty->type);
        $this->assertNotNull($counterparty->inn);
        $this->assertNull($counterparty->kpp);
        $this->assertEquals(12, strlen($counterparty->inn));
    }

    /**
     * @test
     */
    public function counterparty_factory_individual(): void
    {
        $counterparty = CounterpartyFactory::new()->individual()->create();

        $this->assertEquals(CounterpartyTypeEnum::Individual, $counterparty->type);
        $this->assertNull($counterparty->ogrn);
    }

    /**
     * @test
     */
    public function offer_factory_with_product(): void
    {
        $product = ProductFactory::new()->create();
        $offer = OfferFactory::new()->forProduct($product)->create();

        $this->assertEquals($product->id, $offer->product_id);
    }

    /**
     * @test
     */
    public function stock_factory_in_stock(): void
    {
        $offer = OfferFactory::new()->create();
        $warehouse = WarehouseFactory::new()->create();

        $stock = StockFactory::new()
            ->forOffer($offer)
            ->forWarehouse($warehouse)
            ->inStock(100)
            ->create();

        $this->assertEquals(100.000, $stock->quantity);
        $this->assertEquals(0.000, $stock->quantity_reserved);
    }

    /**
     * @test
     */
    public function stock_factory_with_reserved(): void
    {
        $stock = StockFactory::new()
            ->withReserved(15)
            ->create();

        $this->assertGreaterThanOrEqual(15, $stock->quantity);
        $this->assertEquals(15.000, $stock->quantity_reserved);
    }

    /**
     * @test
     */
    public function stock_factory_out_of_stock(): void
    {
        $stock = StockFactory::new()->outOfStock()->create();

        $this->assertEquals(0.000, $stock->quantity);
    }

    /**
     * @test
     */
    public function stock_factory_low_stock(): void
    {
        $stock = StockFactory::new()->lowStock(5)->create();

        $this->assertEquals(5.000, $stock->quantity);
    }
}
