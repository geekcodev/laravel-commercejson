<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Commands\BulkUpsertOrderCommand;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Handlers\Commands\BulkUpsertOrderCommandHandler;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\OrderItem;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use GeekCo\CommerceJson\Repositories\ProductRepository;

beforeEach(function () {
    $this->product = Product::factory()->create();
});

describe('BulkUpsertOrderCommandHandler', function () {
    it('creates a new order from bulk data', function () {
        $orderId = test()->createTestUuid();

        $command = new BulkUpsertOrderCommand(
            id: $orderId,
            status: OrderStatusEnum::New,
            comment: 'Bulk import',
        );

        $handler = new BulkUpsertOrderCommandHandler(
            new OrderRepository(new Order),
            new ProductRepository(new Product),
        );

        $result = $handler->handle($command);

        expect($result)->toBeInstanceOf(Order::class);
        expect($result->id)->toBe($orderId);
        expect($result->status->value)->toBe(OrderStatusEnum::New->value);
        expect($result->comment)->toBe('Bulk import');

        test()->assertDatabaseHas('orders', [
            'id' => $orderId,
            'comment' => 'Bulk import',
        ]);
    });

    it('creates order with items', function () {
        $orderId = test()->createTestUuid();

        $command = new BulkUpsertOrderCommand(
            id: $orderId,
            status: OrderStatusEnum::New,
            items: [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'price' => ['amount' => '500.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
        );

        $handler = new BulkUpsertOrderCommandHandler(
            new OrderRepository(new Order),
            new ProductRepository(new Product),
        );

        $result = $handler->handle($command);

        test()->assertDatabaseHas('orders', ['id' => $orderId]);
        expect($result->items)->toHaveCount(1);
        expect($result->items[0]->product_id)->toBe($this->product->id);
        expect($result->items[0]->quantity)->toBe('2.000');
    });

    it('updates existing order fields', function () {
        $order = Order::factory()->create([
            'status' => OrderStatusEnum::New,
            'comment' => 'Original',
        ]);

        $command = new BulkUpsertOrderCommand(
            id: $order->id,
            status: OrderStatusEnum::Confirmed,
            comment: 'Updated',
        );

        $handler = new BulkUpsertOrderCommandHandler(
            new OrderRepository(new Order),
            new ProductRepository(new Product),
        );

        $result = $handler->handle($command);

        expect($result->status->value)->toBe(OrderStatusEnum::Confirmed->value);
        expect($result->comment)->toBe('Updated');
    });

    it('replaces items on update when items provided', function () {
        $order = Order::factory()->create([
            'status' => OrderStatusEnum::New,
        ]);

        $command = new BulkUpsertOrderCommand(
            id: $order->id,
            items: [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'price' => ['amount' => '100.00', 'currency' => CurrencyEnum::RUB->value],
                ],
            ],
        );

        $handler = new BulkUpsertOrderCommandHandler(
            new OrderRepository(new Order),
            new ProductRepository(new Product),
        );

        $result = $handler->handle($command);

        expect($result->items)->toHaveCount(1);
        expect((float) $result->items[0]->quantity)->toBe(5.0);
    });

    it('does not touch items when items field is null on update', function () {
        $order = Order::factory()->create([
            'status' => OrderStatusEnum::New,
        ]);
        $itemId = test()->createTestUuid();
        OrderItem::factory()->forOrder($order)->create([
            'id' => $itemId,
            'product_id' => $this->product->id,
        ]);

        $command = new BulkUpsertOrderCommand(
            id: $order->id,
            status: OrderStatusEnum::Confirmed,
        );

        $handler = new BulkUpsertOrderCommandHandler(
            new OrderRepository(new Order),
            new ProductRepository(new Product),
        );

        $result = $handler->handle($command);

        expect($result->status->value)->toBe(OrderStatusEnum::Confirmed->value);
        test()->assertDatabaseHas('order_items', ['id' => $itemId]);
    });
});
