<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Commands\PatchOrderCommand;
use GeekCo\CommerceJson\Data\OrderPatchData;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Handlers\Commands\PatchOrderCommandHandler;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use GeekCo\CommerceJson\Repositories\ProductRepository;

describe('PatchOrderCommandHandler', function () {
    it('updates order with linked_documents', function () {
        $order = Order::factory()->create();
        $linkedOrder = Order::factory()->create();

        $patchData = OrderPatchData::from([
            'status' => OrderStatusEnum::Confirmed->value,
            'linked_documents' => [
                [
                    'id' => $linkedOrder->id,
                    'type' => DocumentTypeEnum::Order->value,
                ],
            ],
        ]);

        $handler = new PatchOrderCommandHandler(
            new OrderRepository(new Order),
            new ProductRepository(new Product),
        );

        $result = $handler->handle(new PatchOrderCommand(
            id: $order->id,
            patchData: $patchData,
        ));

        test()->assertDatabaseHas('order_linked_documents', [
            'order_id' => $order->id,
            'linked_order_id' => $linkedOrder->id,
            'type' => DocumentTypeEnum::Order->value,
        ]);
    });

    it('clears linked_documents via empty array', function () {
        $order = Order::factory()->create();
        $linkedOrder = Order::factory()->create();
        $order->linkedDocuments()->attach($linkedOrder->id, ['type' => DocumentTypeEnum::Order->value]);

        $patchData = OrderPatchData::from([
            'linked_documents' => [],
        ]);

        $handler = new PatchOrderCommandHandler(
            new OrderRepository(new Order),
            new ProductRepository(new Product),
        );

        $result = $handler->handle(new PatchOrderCommand(
            id: $order->id,
            patchData: $patchData,
        ));

        test()->assertDatabaseMissing('order_linked_documents', [
            'order_id' => $order->id,
        ]);
    });

    it('does not touch linked_documents when null', function () {
        $order = Order::factory()->create();
        $linkedOrder = Order::factory()->create();
        $order->linkedDocuments()->attach($linkedOrder->id, ['type' => DocumentTypeEnum::Order->value]);

        $patchData = OrderPatchData::from([
            'status' => OrderStatusEnum::Confirmed->value,
            'linked_documents' => null,
        ]);

        $handler = new PatchOrderCommandHandler(
            new OrderRepository(new Order),
            new ProductRepository(new Product),
        );

        $result = $handler->handle(new PatchOrderCommand(
            id: $order->id,
            patchData: $patchData,
        ));

        test()->assertDatabaseHas('order_linked_documents', [
            'order_id' => $order->id,
            'linked_order_id' => $linkedOrder->id,
        ]);
    });
});
