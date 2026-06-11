<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Commands\UpsertProductCommand;
use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Handlers\Commands\UpsertProductCommandHandler;
use GeekCo\CommerceJson\Models\Category;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Repositories\ProductRepository;

beforeEach(function () {
    $this->category = Category::factory()->create();
});

describe('UpsertProductCommandHandler', function () {
    it('creates a new product', function () {
        $productId = test()->createTestUuid();

        $data = ProductData::from([
            'id' => $productId,
            'name' => 'New Product',
            'category_id' => $this->category->id,
            'code' => 'TEST-001',
        ]);

        $repository = new ProductRepository(new Product);
        $handler = new UpsertProductCommandHandler($repository);

        $result = $handler->handle(new UpsertProductCommand($data));

        expect($result)->toBeInstanceOf(Product::class);
        expect($result->id)->toBe($productId);
        expect($result->name)->toBe('New Product');

        test()->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'New Product',
            'category_id' => $this->category->id,
        ]);
    });

    it('updates an existing product', function () {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Original Name',
        ]);

        $data = ProductData::from([
            'id' => $product->id,
            'name' => 'Updated Name',
            'category_id' => $this->category->id,
            'code' => $product->code,
        ]);

        $repository = new ProductRepository(new Product);
        $handler = new UpsertProductCommandHandler($repository);

        $result = $handler->handle(new UpsertProductCommand($data));

        expect($result->name)->toBe('Updated Name');
    });

    it('sets flat attributes from product data', function () {
        $productId = test()->createTestUuid();

        $data = ProductData::from([
            'id' => $productId,
            'name' => 'With Attributes',
            'category_id' => $this->category->id,
            'code' => 'ATTR-001',
            'barcode' => '1234567890123',
            'description' => 'Test description',
            'is_active' => true,
            'weight' => 1.5,
        ]);

        $repository = new ProductRepository(new Product);
        $handler = new UpsertProductCommandHandler($repository);

        $result = $handler->handle(new UpsertProductCommand($data));

        test()->assertDatabaseHas('products', [
            'id' => $productId,
            'barcode' => '1234567890123',
            'description' => 'Test description',
            'is_active' => true,
            'weight' => 1.5,
        ]);
    });
});
