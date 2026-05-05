<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Services;

use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Data\AddressData;
use GeekCo\CommerceJson\Http\Client\Dto\Response\ResponseDto;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Services\ProductService;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Mockery;

class ProductServiceTest extends TestCase
{
    protected ProductService $productService;

    protected Mockery\MockInterface|HttpClientInterface $mockHttp;

    protected Mockery\MockInterface|CommandBusInterface $mockCommandBus;

    protected Mockery\MockInterface|QueryBusInterface $mockQueryBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockHttp = Mockery::mock(HttpClientInterface::class);
        $this->mockCommandBus = Mockery::mock(CommandBusInterface::class);
        $this->mockQueryBus = Mockery::mock(QueryBusInterface::class);
        $this->productService = new ProductService($this->mockHttp, $this->mockCommandBus, $this->mockQueryBus);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function get_products_returns_product_list(): void
    {
        $productData = $this->createProductData();
        $productListData = $this->createProductListData([$productData], ['total' => 1]);

        $mockResponse = [
            'products' => [['id' => $productData->id, 'external_id' => $productData->externalId, 'name' => $productData->name, 'code' => $productData->code, 'category_id' => null, 'is_active' => true]],
            'pagination' => ['page' => 1, 'limit' => 100, 'total' => 1, 'has_next' => false],
        ];
        $responseDto = new ResponseDto(200, [], $mockResponse, new Psr7Response(200, [], json_encode($mockResponse)));

        $this->mockHttp->shouldReceive('get')->once()
            ->with('/catalog/products', ['page' => 1, 'limit' => 100])
            ->andReturn($responseDto);

        $productList = $this->productService->getProducts(page: 1, limit: 100);
        $this->assertCount(1, $productList->products);
        $this->assertEquals(1, $productList->pagination->total);
    }

    /** @test */
    public function get_product_by_id_returns_product(): void
    {
        $productData = $this->createProductData();
        $mockResponse = ['id' => $productData->id, 'external_id' => $productData->externalId, 'name' => $productData->name, 'code' => $productData->code, 'category_id' => null, 'is_active' => true];
        $responseDto = new ResponseDto(200, [], $mockResponse, new Psr7Response(200, [], json_encode($mockResponse)));

        $this->mockHttp->shouldReceive('get')->once()
            ->with("/catalog/products/{$productData->id}", [])
            ->andReturn($responseDto);

        $product = $this->productService->getProduct($productData->id);
        $this->assertEquals($productData->id, $product->id);
        $this->assertEquals($productData->name, $product->name);
        AddressData::factory()->from([]);
    }

    /** @test */
    public function sync_product_dispatches_command(): void
    {
        $productData = $this->createProductData();

        $mockProduct = Mockery::mock(Product::class)->makePartial();
        $mockProduct->shouldIgnoreMissing();
        $mockProduct->id = $productData->id;
        $mockProduct->wasRecentlyCreated = true;

        $this->mockCommandBus->shouldReceive('dispatch')->once()->andReturn($mockProduct);

        $product = $this->productService->syncProduct($productData);
        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals($productData->id, $product->id);
    }

    /** @test */
    public function sync_products_batch_returns_stats(): void
    {
        $productsData = [$this->createProductData(), $this->createProductData()];

        $mockProduct1 = Mockery::mock(Product::class)->makePartial();
        $mockProduct1->shouldIgnoreMissing();
        $mockProduct1->wasRecentlyCreated = true;
        $mockProduct2 = Mockery::mock(Product::class)->makePartial();
        $mockProduct2->shouldIgnoreMissing();
        $mockProduct2->wasRecentlyCreated = false;

        $this->mockCommandBus->shouldReceive('dispatch')->twice()->andReturn($mockProduct1, $mockProduct2);

        $stats = $this->productService->syncProducts($productsData);
        $this->assertEquals(1, $stats['created']);
        $this->assertEquals(1, $stats['updated']);
    }
}
