<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Services;

use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Database\Factories\CategoryFactory;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Models\Category; // Added for mocking
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Services\ProductService;
use GeekCo\CommerceJson\Support\Mappers\ProductMapper;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection; // Required for mock responses
use Mockery;

/**
 * Тесты для ProductService
 *
 * @covers \GeekCo\CommerceJson\Services\ProductService
 */
class ProductServiceTest extends TestCase
{
    protected ProductService $productService;

    protected \Mockery\MockInterface|CommerceJsonConnector $mockConnector;

    protected \Mockery\MockInterface|ProductMapper $mockMapper; // Added mock mapper

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConnector = Mockery::mock(CommerceJsonConnector::class);
        $this->mockMapper = Mockery::mock(ProductMapper::class); // Initialize mock mapper

        $this->productService = new ProductService(
            $this->mockConnector,
            $this->mockMapper // Inject mock mapper
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function get_products_returns_product_list(): void
    {
        $categoryId = $this->createTestUuid();
        $mockResponseContent = [
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
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/products', ['page' => 1, 'limit' => 100, 'category_id' => $categoryId])
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

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
        $updatedAfter = now()->subHour();
        $categoryId = $this->createTestUuid();
        $mockResponseContent = [
            'products' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 100,
                'total' => 0,
                'has_next' => false,
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/products', [
                'page' => 1,
                'limit' => 50,
                'category_id' => $categoryId,
                'is_active' => true,
                'updated_after' => $updatedAfter->format(\DateTime::ATOM),
            ])
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        $this->productService->getProducts(
            page: 1,
            limit: 50,
            categoryId: $categoryId,
            isActive: true,
            updatedAfter: $updatedAfter
        );
    }

    /**
     * @test
     */
    public function get_product_by_id_returns_product(): void
    {
        $productId = $this->createTestUuid();
        $mockResponseContent = [
            'id' => $productId,
            'name' => 'Test Product',
            'code' => 'TEST-001',
            'category_id' => $this->createTestUuid(),
            'is_active' => true,
            'tax_rate' => 20.00,
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with("/catalog/products/{$productId}", [])
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

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
        $errorResponseContent = [
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Product not found',
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with("/catalog/products/{$nonExistentId}", [])
            ->andThrow(new \RuntimeException('Product not found', 404));

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
        $productDataArray = [
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
        $mockResponseContent = [
            'success' => true,
            'processed' => 1,
            'errors' => [],
        ];

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/catalog/products', Mockery::any(), null) // Use Mockery::any() for the data array
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        $result = $this->productService->importProducts($productDataArray);

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
        $productDataArray = ['products' => [['id' => $this->createTestUuid(), 'name' => 'Test']]];
        $mockResponseContent = [
            'success' => true,
            'processed' => 1,
        ];

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/catalog/products', Mockery::any(), $idempotencyKey) // Use Mockery::any() for the data array
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        $this->productService->importProducts(
            $productDataArray,
            $idempotencyKey
        );
    }

    /**
     * @test
     */
    public function deactivate_product_sets_inactive(): void
    {
        $productId = $this->createTestUuid();
        $mockResponseContent = [
            'id' => $productId,
            'is_active' => false,
            'deleted_at' => now()->toIso8601String(),
        ];

        $this->mockConnector->shouldReceive('delete')
            ->once()
            ->with("/catalog/products/{$productId}")
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

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
        $mockResponsePage1 = [
            'products' => [
                ['id' => $this->createTestUuid(), 'name' => 'Product 1', 'category_id' => $this->createTestUuid(), 'is_active' => true],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 1,
                'total' => 3,
                'has_next' => true,
            ],
        ];
        $mockResponsePage2 = [
            'products' => [
                ['id' => $this->createTestUuid(), 'name' => 'Product 2', 'category_id' => $this->createTestUuid(), 'is_active' => true],
            ],
            'pagination' => [
                'page' => 2,
                'limit' => 1,
                'total' => 3,
                'has_next' => true,
            ],
        ];
        $mockResponsePage3 = [
            'products' => [
                ['id' => $this->createTestUuid(), 'name' => 'Product 3', 'category_id' => $this->createTestUuid(), 'is_active' => true],
            ],
            'pagination' => [
                'page' => 3,
                'limit' => 1,
                'total' => 3,
                'has_next' => false,
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/products', ['page' => 1, 'limit' => 100])
            ->andReturn(new Response(200, [], json_encode($mockResponsePage1)));

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/products', ['page' => 2, 'limit' => 100])
            ->andReturn(new Response(200, [], json_encode($mockResponsePage2)));

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/products', ['page' => 3, 'limit' => 100])
            ->andReturn(new Response(200, [], json_encode($mockResponsePage3)));

        $products = iterator_to_array($this->productService->lazyGetProducts());

        $this->assertCount(3, $products);
    }

    /**
     * @test
     */
    public function get_all_products_returns_collection(): void
    {
        $mockResponseContent = [
            'products' => [
                ['id' => $this->createTestUuid(), 'name' => 'Product 1', 'category_id' => $this->createTestUuid(), 'is_active' => true],
                ['id' => $this->createTestUuid(), 'name' => 'Product 2', 'category_id' => $this->createTestUuid(), 'is_active' => true],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 100,
                'total' => 2,
                'has_next' => false,
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/products', ['page' => 1, 'limit' => 100])
            ->andReturn(new Response(200, [], json_encode($mockResponseContent)));

        $products = $this->productService->getAllProducts();

        $this->assertInstanceOf(Collection::class, $products);
        $this->assertCount(2, $products);
    }

    /**
     * @test
     */
    public function sync_product_creates_or_updates_model(): void
    {
        // Mock CategoryFactory::new()->create()
        $categoryId = $this->createTestUuid();
        $mockCategory = Mockery::mock(Category::class);
        $mockCategory->shouldReceive('getAttribute')->with('id')->andReturn($categoryId); // Mock getAttribute('id')
        Mockery::mock('alias:GeekCo\CommerceJson\Database\Factories\CategoryFactory')
            ->shouldReceive('new->create')
            ->andReturn($mockCategory);

        $productId = $this->createTestUuid();
        $productData = new ProductData(
            id: $productId,
            externalId: 'EXT-001',
            name: 'Synced Product',
            code: 'SYNC-001',
            categoryId: $categoryId,
            isActive: true,
        );

        // Mock ProductMapper->toModel to return a mock Product model
        $mockProduct = Mockery::mock(Product::class);
        $mockProduct->id = $productId;
        $mockProduct->name = 'Synced Product';
        $mockProduct->is_active = true;
        $mockProduct->wasRecentlyCreated = true; // Simulate creation for first call

        $this->mockMapper->shouldReceive('toModel')
            ->once()
            ->with(Mockery::on(function (ProductData $arg) use ($productData) {
                // Ensure the correct ProductData is passed to the mapper
                return $arg->id === $productData->id;
            }))
            ->andReturn($mockProduct);

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
        // Mock CategoryFactory::new()->create()
        $categoryId = $this->createTestUuid();
        $mockCategory = Mockery::mock(Category::class);
        $mockCategory->shouldReceive('getAttribute')->with('id')->andReturn($categoryId); // Mock getAttribute('id')
        Mockery::mock('alias:GeekCo\CommerceJson\Database\Factories\CategoryFactory')
            ->shouldReceive('new->create')
            ->andReturn($mockCategory);

        $productsData = [
            new ProductData(
                id: $this->createTestUuid(),
                name: 'Product 1',
                categoryId: $categoryId,
                isActive: true,
            ),
            new ProductData(
                id: $this->createTestUuid(),
                name: 'Product 2',
                categoryId: $categoryId,
                isActive: true,
            ),
        ];

        // Mock ProductMapper->toModel for each product in the batch
        $mockProduct1 = Mockery::mock(Product::class);
        $mockProduct1->wasRecentlyCreated = true; // Simulate creation
        $mockProduct2 = Mockery::mock(Product::class);
        $mockProduct2->wasRecentlyCreated = false; // Simulate update

        $this->mockMapper->shouldReceive('toModel')
            ->once()
            ->with(Mockery::on(function (ProductData $arg) use ($productsData) {
                return $arg->id === $productsData[0]->id;
            }))
            ->andReturn($mockProduct1);

        $this->mockMapper->shouldReceive('toModel')
            ->once()
            ->with(Mockery::on(function (ProductData $arg) use ($productsData) {
                return $arg->id === $productsData[1]->id;
            }))
            ->andReturn($mockProduct2);

        $stats = $this->productService->syncProducts($productsData);

        $this->assertArrayHasKey('created', $stats);
        $this->assertArrayHasKey('updated', $stats);
        $this->assertEquals(1, $stats['created']); // One created
        $this->assertEquals(1, $stats['updated']); // One updated
    }
}
