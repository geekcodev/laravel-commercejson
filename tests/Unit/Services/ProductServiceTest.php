<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Services;

use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Database\Factories\CategoryFactory;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Services\ProductService;
use GeekCo\CommerceJson\Support\Mappers\ProductMapper;
use GeekCo\CommerceJson\Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Тесты для ProductService
 *
 * @covers \GeekCo\CommerceJson\Services\ProductService
 */
class ProductServiceTest extends TestCase
{
    protected ProductService $productService;

    protected CommerceJsonConnector $connector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = new CommerceJsonConnector(
            baseUrl: 'https://api.test.com/v1',
            authToken: 'test-token',
        );

        $this->productService = new ProductService(
            $this->connector,
            new ProductMapper
        );
    }

    /**
     * @test
     */
    public function get_products_returns_product_list(): void
    {
        $categoryId = $this->createTestUuid();

        Http::fake([
            '*/catalog/products*' => Http::response([
                'products' => [
                    [
                        'id' => $this->createTestUuid(),
                        'name' => 'Product 1',
                        'category_id' => $categoryId,
                        'is_active' => true,
                    ],
                    [
                        'id' => $this->createTestUuid(),
                        'name' => 'Product 2',
                        'category_id' => $categoryId,
                        'is_active' => true,
                    ],
                ],
                'pagination' => [
                    'page' => 1,
                    'limit' => 100,
                    'total' => 2,
                    'has_next' => false,
                ],
            ], 200),
        ]);

        $productList = $this->productService->getProducts(
            page: 1,
            limit: 100,
            categoryId: $categoryId
        );

        $this->assertCount(2, $productList->products);
        $this->assertEquals(2, $productList->pagination->total);
        $this->assertFalse($productList->pagination->hasNext);
    }

    /**
     * @test
     */
    public function get_products_with_filters(): void
    {
        Http::fake([
            '*/catalog/products*' => Http::response([
                'products' => [],
                'pagination' => [
                    'page' => 1,
                    'limit' => 100,
                    'total' => 0,
                    'has_next' => false,
                ],
            ], 200),
        ]);

        $updatedAfter = now()->subHour()->toIso8601String();

        $this->productService->getProducts(
            page: 1,
            limit: 50,
            categoryId: $this->createTestUuid(),
            isActive: true,
            updatedAfter: new \DateTime($updatedAfter)
        );

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'catalog/products')
                && $request['page'] == 1
                && $request['limit'] == 50
                && $request['is_active'] === true
                && isset($request['updated_after']);
        });
    }

    /**
     * @test
     */
    public function get_product_by_id_returns_product(): void
    {
        $productId = $this->createTestUuid();

        Http::fake([
            "*/catalog/products/{$productId}" => Http::response([
                'id' => $productId,
                'name' => 'Test Product',
                'code' => 'TEST-001',
                'category_id' => $this->createTestUuid(),
                'is_active' => true,
                'tax_rate' => 20.00,
            ], 200),
        ]);

        $product = $this->productService->getProduct($productId);

        $this->assertEquals($productId, $product->id);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals('TEST-001', $product->code);
    }

    /**
     * @test
     */
    public function get_product_by_id_returns_404(): void
    {
        $nonExistentId = $this->createTestUuid();

        Http::fake([
            "*/catalog/products/{$nonExistentId}" => Http::response([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Product not found',
                ],
            ], 404),
        ]);

        $this->expectException(\RuntimeException::class);

        $this->productService->getProduct($nonExistentId);
    }

    /**
     * @test
     */
    public function import_products_creates_products(): void
    {
        $productId = $this->createTestUuid();
        $categoryId = $this->createTestUuid();

        Http::fake([
            '*/catalog/products' => Http::response([
                'success' => true,
                'processed' => 1,
                'errors' => [],
            ], 200),
        ]);

        $productData = [
            'products' => [
                [
                    'id' => $productId,
                    'name' => 'Imported Product',
                    'category_id' => $categoryId,
                    'code' => 'IMP-001',
                    'is_active' => true,
                ],
            ],
        ];

        $result = $this->productService->importProducts($productData);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->processed);
        $this->assertEmpty($result->errors);
    }

    /**
     * @test
     */
    public function import_products_with_idempotency_key(): void
    {
        $idempotencyKey = $this->createTestUuid();

        Http::fake([
            '*/catalog/products' => Http::response([
                'success' => true,
                'processed' => 1,
            ], 200),
        ]);

        $this->productService->importProducts(
            ['products' => [['id' => $this->createTestUuid(), 'name' => 'Test']]],
            $idempotencyKey
        );

        Http::assertSent(function ($request) use ($idempotencyKey) {
            return $request->header('X-Idempotency-Key')[0] === $idempotencyKey;
        });
    }

    /**
     * @test
     */
    public function deactivate_product_sets_inactive(): void
    {
        $productId = $this->createTestUuid();

        Http::fake([
            "*/catalog/products/{$productId}" => Http::response([
                'id' => $productId,
                'is_active' => false,
                'deleted_at' => now()->toIso8601String(),
            ], 200),
        ]);

        $product = $this->productService->deactivateProduct($productId);

        $this->assertFalse($product->isActive);
        $this->assertNotNull($product->deletedAt);
    }

    /**
     * @test
     */
    public function lazy_get_products_yields_all_products(): void
    {
        $page = 1;

        Http::fake([
            '*/catalog/products*' => function ($request) use (&$page) {
                $response = [
                    'products' => [
                        ['id' => $this->createTestUuid(), 'name' => "Product {$page}"],
                    ],
                    'pagination' => [
                        'page' => $page,
                        'limit' => 1,
                        'total' => 3,
                        'has_next' => $page < 3,
                    ],
                ];
                $page++;

                return Http::response($response, 200);
            },
        ]);

        $products = iterator_to_array($this->productService->lazyGetProducts());

        $this->assertCount(3, $products);
    }

    /**
     * @test
     */
    public function get_all_products_returns_collection(): void
    {
        Http::fake([
            '*/catalog/products*' => Http::response([
                'products' => [
                    ['id' => $this->createTestUuid(), 'name' => 'Product 1'],
                    ['id' => $this->createTestUuid(), 'name' => 'Product 2'],
                ],
                'pagination' => [
                    'page' => 1,
                    'limit' => 100,
                    'total' => 2,
                    'has_next' => false,
                ],
            ], 200),
        ]);

        $products = $this->productService->getAllProducts();

        $this->assertInstanceOf(Collection::class, $products);
        $this->assertCount(2, $products);
    }

    /**
     * @test
     */
    public function sync_product_creates_or_updates_model(): void
    {
        $category = CategoryFactory::new()->create();
        $productId = $this->createTestUuid();

        $productData = new ProductData(
            id: $productId,
            externalId: 'EXT-001',
            name: 'Synced Product',
            code: 'SYNC-001',
            categoryId: $category->id,
            isActive: true,
        );

        $product = $this->productService->syncProduct($productData);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals($productId, $product->id);
        $this->assertEquals('Synced Product', $product->name);
        $this->assertTrue($product->is_active);
    }

    /**
     * @test
     */
    public function sync_products_batch_returns_stats(): void
    {
        $category = CategoryFactory::new()->create();

        $productsData = [
            new ProductData(
                id: $this->createTestUuid(),
                name: 'Product 1',
                categoryId: $category->id,
                isActive: true,
            ),
            new ProductData(
                id: $this->createTestUuid(),
                name: 'Product 2',
                categoryId: $category->id,
                isActive: true,
            ),
        ];

        $stats = $this->productService->syncProducts($productsData);

        $this->assertArrayHasKey('created', $stats);
        $this->assertArrayHasKey('updated', $stats);
        $this->assertEquals(2, $stats['created'] + $stats['updated']);
    }
}
