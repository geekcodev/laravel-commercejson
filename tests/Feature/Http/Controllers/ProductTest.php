<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Queries\GetProductQuery;
use GeekCo\CommerceJson\Queries\GetProductsQuery;

describe('ProductController', function () {
    describe('GET /catalog/products', function () {
        it('returns paginated products list', function () {
            $queryBus = mockQueryBus();
            $mockResult = Mockery::mock(stdClass::class);
            $mockResult->shouldReceive('items')->andReturn(collect([
                test()->createProductData(),
                test()->createProductData(),
            ]));
            $mockResult->shouldReceive('currentPage')->andReturn(1);
            $mockResult->shouldReceive('lastPage')->andReturn(1);
            $mockResult->shouldReceive('perPage')->andReturn(15);
            $mockResult->shouldReceive('total')->andReturn(2);

            $queryBus->shouldReceive('ask')
                ->once()
                ->with(Mockery::type(GetProductsQuery::class))
                ->andReturn($mockResult);

            $response = $this->getJson('/api/commercejson/catalog/products');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name', 'code'],
                    ],
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ])
                ->assertJson([
                    'meta' => ['total' => 2],
                ]);
        });
    });

    describe('GET /catalog/products/{id}', function () {
        it('returns a single product', function () {
            $queryBus = mockQueryBus();
            $productId = test()->createTestUuid();
            $product = Product::factory()->make([
                'id' => $productId,
                'name' => 'Test Product',
                'code' => 'TEST-001',
                'category_id' => test()->createTestUuid(),
            ]);

            $queryBus->shouldReceive('ask')
                ->once()
                ->with(Mockery::type(GetProductQuery::class))
                ->andReturn($product);

            $response = $this->getJson("/api/commercejson/catalog/products/{$productId}");

            $response->assertStatus(200)
                ->assertJson([
                    'id' => $productId,
                    'name' => 'Test Product',
                ]);
        });
    });

    describe('POST /catalog/products', function () {
        it('creates a product and returns 200', function () {
            $commandBus = mockCommandBus();
            $productId = test()->createTestUuid();
            $categoryId = test()->createTestUuid();

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->andReturn(null);

            $response = $this->postJson('/api/commercejson/catalog/products', [
                'products' => [
                    [
                        'id' => $productId,
                        'name' => 'New Product',
                        'code' => 'NEW-001',
                        'category_id' => $categoryId,
                    ],
                ],
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 1,
                    'errors' => [],
                ]);
        });
    });

    describe('DELETE /catalog/products/{id}', function () {
        it('soft-deletes a product', function () {
            $queryBus = mockQueryBus();
            $productId = test()->createTestUuid();
            $product = Product::factory()->create([
                'id' => $productId,
                'name' => 'Test Product',
                'code' => 'TST',
                'category_id' => test()->createTestUuid(),
            ]);

            $queryBus->shouldReceive('ask')
                ->once()
                ->andReturn($product);

            $response = $this->deleteJson("/api/commercejson/catalog/products/{$productId}");

            $response->assertStatus(200)
                ->assertJson([
                    'id' => $productId,
                    'is_active' => false,
                ]);
            expect($response->json('deleted_at'))->not->toBeNull();
        });
    });
});
