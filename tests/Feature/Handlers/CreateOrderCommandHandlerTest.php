<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Commands\CreateOrderCommand;
use GeekCo\CommerceJson\Data\OrderCreateData;
use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use GeekCo\CommerceJson\Handlers\Commands\CreateOrderCommandHandler;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use GeekCo\CommerceJson\Repositories\ProductRepository;
use GeekCo\CommerceJson\Services\OfferPriceResolver;

beforeEach(function () {
    $this->product = Product::factory()->create();
});

describe('CreateOrderCommandHandler', function () {
    it('creates order with linked_documents', function () {
        $linkedOrder = Order::factory()->create();

        $createData = OrderCreateData::from([
            'document_type' => DocumentTypeEnum::Invoice->value,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
            'linked_documents' => [
                [
                    'id' => $linkedOrder->id,
                    'type' => DocumentTypeEnum::Order->value,
                ],
            ],
        ]);

        $handler = new CreateOrderCommandHandler(
            new OrderRepository(new Order),
            new ProductRepository(new Product),
            new OfferPriceResolver,
        );

        $result = $handler->handle(new CreateOrderCommand($createData));

        expect($result)->toBeInstanceOf(Order::class);
        expect($result->linkedDocuments)->toHaveCount(1);
        expect($result->linkedDocuments[0]->id)->toBe($linkedOrder->id);
    });
});
