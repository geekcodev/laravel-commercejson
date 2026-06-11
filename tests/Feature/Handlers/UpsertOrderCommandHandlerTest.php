<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Commands\UpsertOrderCommand;
use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Data\OrderDeliveryTrackData;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Handlers\Commands\UpsertOrderCommandHandler;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Repositories\OrderRepository;

describe('UpsertOrderCommandHandler', function () {
    it('creates a new order', function () {
        $orderId = test()->createTestUuid();

        $orderData = OrderData::from([
            'id' => $orderId,
            'status' => OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order->value,
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => test()->createTestUuid(),
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
        $handler = new UpsertOrderCommandHandler($repository);

        $result = $handler->handle(new UpsertOrderCommand($orderData));

        expect($result)->toBeInstanceOf(Order::class);
        expect($result->id)->toBe($orderId);
        expect($result->status->value)->toBe(OrderStatusEnum::New->value);

        test()->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => OrderStatusEnum::New->value,
            'number' => 'ORD-TEST-001',
        ]);
    });

    it('updates an existing order without overwriting number', function () {
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
                    'product_id' => test()->createTestUuid(),
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
        $handler = new UpsertOrderCommandHandler($repository);

        $result = $handler->handle(new UpsertOrderCommand($orderData));

        expect($result->status->value)->toBe(OrderStatusEnum::Confirmed->value);
        expect($result->number)->toBe($originalNumber);
    });

    it('applies delivery tracking when provided', function () {
        $orderId = test()->createTestUuid();

        $orderData = OrderData::from([
            'id' => $orderId,
            'status' => OrderStatusEnum::New,
            'document_type' => DocumentTypeEnum::Order->value,
            'items' => [
                [
                    'id' => test()->createTestUuid(),
                    'product_id' => test()->createTestUuid(),
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
        $handler = new UpsertOrderCommandHandler($repository);

        $result = $handler->handle(new UpsertOrderCommand($orderData, $deliveryTrack));

        test()->assertDatabaseHas('orders', [
            'id' => $orderId,
            'delivery_tracking_number' => 'TRACK-123',
        ]);
    });
});
