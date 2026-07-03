<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Commands\UpsertOrderCommand;
use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Data\OrderDeliveryTrackData;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\DeliveryMethodEnum;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Enums\PaymentMethodEnum;
use GeekCo\CommerceJson\Enums\PaymentStatusEnum;
use GeekCo\CommerceJson\Handlers\Commands\UpsertOrderCommandHandler;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use GeekCo\CommerceJson\Repositories\ProductRepository;

describe('UpsertOrderCommandHandler', function () {
    it('creates a new order', function () {
        $orderId = test()->createTestUuid();
        $product = Product::factory()->create();

        $orderData = OrderData::from([
            'id' => $orderId,
            'status' => OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order->value,
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
            'totals' => [
                'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
            ],
            'number' => 'ORD-TEST-001',
        ]);

        $repository = new OrderRepository(new Order);
        $handler = new UpsertOrderCommandHandler($repository, new ProductRepository(new Product));

        $result = $handler->handle(new UpsertOrderCommand($orderData));

        expect($result)->toBeInstanceOf(Order::class);
        expect($result->id)->toBe($orderId);
        expect($result->status->value)->toBe(OrderStatusEnum::New->value);

        test()->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => OrderStatusEnum::New->value,
            'number' => 'ORD-TEST-001',
        ]);

        test()->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $product->id,
        ]);
    });

    it('updates an existing order without overwriting number', function () {
        $product = Product::factory()->create();
        $order = Order::factory()->create([
            'number' => 'ORD-ORIGINAL',
            'status' => OrderStatusEnum::New,
        ]);
        $originalNumber = $order->number;

        $orderData = OrderData::from([
            'id' => $order->id,
            'status' => OrderStatusEnum::Confirmed,
            'document_type' => DocumentTypeEnum::Order->value,
            'number' => 'ORD-SHOULD-NOT-CHANGE',
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
            'totals' => [
                'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
            ],
        ]);

        $repository = new OrderRepository(new Order);
        $handler = new UpsertOrderCommandHandler($repository, new ProductRepository(new Product));

        $result = $handler->handle(new UpsertOrderCommand($orderData));

        expect($result->status->value)->toBe(OrderStatusEnum::Confirmed->value);
        expect($result->number)->toBe($originalNumber);
    });

    it('applies delivery tracking when provided', function () {
        $orderId = test()->createTestUuid();
        $product = Product::factory()->create();

        $orderData = OrderData::from([
            'id' => $orderId,
            'status' => OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order->value,
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
            'totals' => [
                'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
            ],
            'number' => 'ORD-DELIVERY',
        ]);

        $deliveryTrack = new OrderDeliveryTrackData(
            tracking_number: 'TRACK-123',
            shipped_at: now(),
            estimated_date: now()->addDays(3),
        );

        $repository = new OrderRepository(new Order);
        $handler = new UpsertOrderCommandHandler($repository, new ProductRepository(new Product));

        $result = $handler->handle(new UpsertOrderCommand($orderData, $deliveryTrack));

        test()->assertDatabaseHas('orders', [
            'id' => $orderId,
            'delivery_tracking_number' => 'TRACK-123',
        ]);
    });

    it('creates an order with customer data', function () {
        $orderId = test()->createTestUuid();
        $product = Product::factory()->create();

        $orderData = OrderData::from([
            'id' => $orderId,
            'status' => OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order->value,
            'number' => 'ORD-CUSTOMER',
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
            'totals' => [
                'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
            ],
            'customer' => [
                'name' => 'Ivan Petrov',
                'phone' => '+7-999-123-45-67',
                'email' => 'ivan@example.com',
            ],
        ]);

        $repository = new OrderRepository(new Order);
        $handler = new UpsertOrderCommandHandler($repository, new ProductRepository(new Product));

        $result = $handler->handle(new UpsertOrderCommand($orderData));

        test()->assertDatabaseHas('orders', [
            'id' => $orderId,
            'customer_name' => 'Ivan Petrov',
            'customer_phone' => '+7-999-123-45-67',
            'customer_email' => 'ivan@example.com',
        ]);
    });

    it('creates an order with delivery data', function () {
        $orderId = test()->createTestUuid();
        $product = Product::factory()->create();

        $orderData = OrderData::from([
            'id' => $orderId,
            'status' => OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order->value,
            'number' => 'ORD-DELIVERY',
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
            'totals' => [
                'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
            ],
            'delivery' => [
                'type' => DeliveryMethodEnum::Courier->value,
                'method_name' => 'Express',
                'cost' => ['amount' => '500.00', 'currency' => CurrencyEnum::RUB->value],
                'address' => [
                    'country' => 'RU',
                    'city' => 'Moscow',
                    'street' => 'Tverskaya',
                    'house' => '1',
                    'postal_code' => '101000',
                ],
            ],
        ]);

        $repository = new OrderRepository(new Order);
        $handler = new UpsertOrderCommandHandler($repository, new ProductRepository(new Product));

        $result = $handler->handle(new UpsertOrderCommand($orderData));

        test()->assertDatabaseHas('orders', [
            'id' => $orderId,
            'delivery_type' => 'courier',
            'delivery_method_name' => 'Express',
            'delivery_cost_amount' => 500.00,
            'delivery_address_city' => 'Moscow',
            'delivery_address_street' => 'Tverskaya',
            'delivery_address_house' => '1',
        ]);
    });

    it('creates an order with payment data', function () {
        $orderId = test()->createTestUuid();
        $product = Product::factory()->create();

        $paidAt = now()->subDay();

        $orderData = OrderData::from([
            'id' => $orderId,
            'status' => OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order->value,
            'number' => 'ORD-PAYMENT',
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
            'totals' => [
                'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
            ],
            'payment' => [
                'type' => PaymentMethodEnum::Card->value,
                'status' => PaymentStatusEnum::Paid->value,
                'amount' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                'paid_at' => $paidAt->toIso8601String(),
            ],
        ]);

        $repository = new OrderRepository(new Order);
        $handler = new UpsertOrderCommandHandler($repository, new ProductRepository(new Product));

        $result = $handler->handle(new UpsertOrderCommand($orderData));

        test()->assertDatabaseHas('orders', [
            'id' => $orderId,
            'payment_type' => 'card',
            'payment_status' => 'paid',
            'payment_amount' => 100.00,
        ]);
    });

    it('creates an order with custom attributes', function () {
        $orderId = test()->createTestUuid();
        $product = Product::factory()->create();

        $orderData = OrderData::from([
            'id' => $orderId,
            'status' => OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order->value,
            'number' => 'ORD-CUSTOM-ATTRS',
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
            'totals' => [
                'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
            ],
            'custom_attributes' => [
                ['key' => 'color', 'value' => 'red'],
                ['key' => 'quantity', 'value' => 10],
                ['key' => 'is_gift', 'value' => true],
                ['key' => 'tags', 'value' => ['sale', 'new']],
            ],
        ]);

        $repository = new OrderRepository(new Order);
        $handler = new UpsertOrderCommandHandler($repository, new ProductRepository(new Product));

        $result = $handler->handle(new UpsertOrderCommand($orderData));

        expect($result->customAttributes()->count())->toBe(4);
        expect($result->customAttributes()->where('key', 'color')->first()->value_string)->toBe('red');
        expect($result->customAttributes()->where('key', 'quantity')->first()->value_number)->toBe('10.0000');
        expect($result->customAttributes()->where('key', 'is_gift')->first()->value_boolean)->toBeTrue();
        expect($result->customAttributes()->where('key', 'tags')->first()->value_json)->toBe(['sale', 'new']);
    });

    it('creates an order with signatories', function () {
        $orderId = test()->createTestUuid();
        $product = Product::factory()->create();

        $orderData = OrderData::from([
            'id' => $orderId,
            'status' => OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order->value,
            'number' => 'ORD-SIGNATORIES',
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
            'totals' => [
                'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
            ],
            'signatories' => [
                [
                    'first_name' => 'Ivan',
                    'last_name' => 'Petrov',
                    'position' => 'Director',
                    'basis' => 'Charter',
                ],
                [
                    'first_name' => 'Maria',
                    'last_name' => 'Sidorova',
                    'position' => 'Accountant',
                ],
            ],
        ]);

        $repository = new OrderRepository(new Order);
        $handler = new UpsertOrderCommandHandler($repository, new ProductRepository(new Product));

        $result = $handler->handle(new UpsertOrderCommand($orderData));

        expect($result->signatories()->count())->toBe(2);
        expect($result->signatories()->where('first_name', 'Ivan')->first()->position)->toBe('Director');
    });

    it('creates an order with linked documents', function () {
        $orderId = test()->createTestUuid();
        $linkedOrder = Order::factory()->create();
        $product = Product::factory()->create();

        $orderData = OrderData::from([
            'id' => $orderId,
            'status' => OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order->value,
            'number' => 'ORD-LINKED',
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
            'totals' => [
                'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
            ],
            'linked_documents' => [
                ['id' => $linkedOrder->id, 'type' => DocumentTypeEnum::Order->value],
            ],
        ]);

        $repository = new OrderRepository(new Order);
        $handler = new UpsertOrderCommandHandler($repository, new ProductRepository(new Product));

        $result = $handler->handle(new UpsertOrderCommand($orderData));

        test()->assertDatabaseHas('order_linked_documents', [
            'order_id' => $orderId,
            'linked_order_id' => $linkedOrder->id,
        ]);
    });

    it('replaces items when updating an existing order', function () {
        $product = Product::factory()->create();
        $product2 = Product::factory()->create();
        $order = Order::factory()->create();

        // Initial creation with one item
        $initialData = OrderData::from([
            'id' => $order->id,
            'status' => OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order->value,
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
            'totals' => [
                'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
            ],
        ]);

        $repository = new OrderRepository(new Order);
        $handler = new UpsertOrderCommandHandler($repository, new ProductRepository(new Product));

        $handler->handle(new UpsertOrderCommand($initialData));
        expect($order->items()->count())->toBe(1);

        // Update with different item
        $updateData = OrderData::from([
            'id' => $order->id,
            'status' => OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order->value,
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => $product2->id,
                    'quantity' => 2,
                    'price' => ['amount' => '200.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '400.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
            'totals' => [
                'subtotal' => ['amount' => '400.00', 'currency' => CurrencyEnum::RUB->value],
                'total' => ['amount' => '400.00', 'currency' => CurrencyEnum::RUB->value],
            ],
        ]);

        $result = $handler->handle(new UpsertOrderCommand($updateData));

        expect($result->items->count())->toBe(1);
        expect($result->items->first()->product_id)->toBe($product2->id);
        expect((float) $result->items->first()->quantity)->toBe(2.0);
    });

    it('fills totals with all optional sub-fields', function () {
        $orderId = test()->createTestUuid();
        $product = Product::factory()->create();

        $orderData = OrderData::from([
            'id' => $orderId,
            'status' => OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order->value,
            'number' => 'ORD-TOTALS-FULL',
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                    'total' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
            'totals' => [
                'subtotal' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                'discount' => ['amount' => '10.00', 'currency' => CurrencyEnum::RUB->value],
                'delivery' => ['amount' => '20.00', 'currency' => CurrencyEnum::RUB->value],
                'tax' => ['amount' => '18.00', 'currency' => CurrencyEnum::RUB->value],
                'total' => ['amount' => '128.00', 'currency' => CurrencyEnum::RUB->value],
            ],
        ]);

        $repository = new OrderRepository(new Order);
        $handler = new UpsertOrderCommandHandler($repository, new ProductRepository(new Product));

        $result = $handler->handle(new UpsertOrderCommand($orderData));

        test()->assertDatabaseHas('orders', [
            'id' => $orderId,
            'totals_subtotal_amount' => 100.00,
            'totals_discount_amount' => 10.00,
            'totals_delivery_amount' => 20.00,
            'totals_tax_amount' => 18.00,
            'totals_total_amount' => 128.00,
        ]);
    });
});
