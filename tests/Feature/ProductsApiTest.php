<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature;

// Potentially needed if Product API has business rules
use Carbon\Carbon;
use GeekCo\CommerceJson\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Tests\TestCase; // Still needed if the connector itself maps it
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;

class ProductsApiTest extends TestCase
{
    protected CommerceJsonConnector|MockInterface $mockConnector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConnector = Mockery::mock(CommerceJsonConnector::class);
        $this->app->instance(CommerceJsonConnector::class, $this->mockConnector);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function get_products_returns_paginated_list(): void
    {
        $mockProductsData = [
            'products' => [
                ['id' => $this->createTestUuid(), 'name' => 'Product 1', 'category_id' => $this->createTestUuid(), 'is_active' => true, 'updated_at' => now()->toIso8601String()],
                ['id' => $this->createTestUuid(), 'name' => 'Product 2', 'category_id' => $this->createTestUuid(), 'is_active' => true, 'updated_at' => now()->toIso8601String()],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 2,
                'has_next' => false,
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/products', ['page' => 1, 'limit' => 10])
            ->andReturn(new Response(200, [], json_encode($mockProductsData)));

        $connector = $this->app->make(CommerceJsonConnector::class);
        $response = $connector->get('/catalog/products', ['page' => 1, 'limit' => 10]);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $responseData['products']);
        $this->assertEquals(1, $responseData['pagination']['page']);
        $this->assertEquals(10, $responseData['pagination']['limit']);
        $this->assertIsBool($responseData['pagination']['has_next']);
        $this->assertValidUuid($responseData['products'][0]['id']);
        $this->assertIsString($responseData['products'][0]['name']);
        $this->assertIsBool($responseData['products'][0]['is_active']);
    }

    /** @test */
    public function get_products_with_filters(): void
    {
        $categoryId = $this->createTestUuid();
        $mockProductsData = [
            'products' => [
                ['id' => $this->createTestUuid(), 'name' => 'Filtered Product', 'category_id' => $categoryId, 'is_active' => true, 'updated_at' => now()->toIso8601String()],
            ],
            'pagination' => ['page' => 1, 'limit' => 1, 'total' => 1, 'has_next' => false],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/products', ['category_id' => $categoryId, 'is_active' => true])
            ->andReturn(new Response(200, [], json_encode($mockProductsData)));

        $connector = $this->app->make(CommerceJsonConnector::class);
        $response = $connector->get('/catalog/products', ['category_id' => $categoryId, 'is_active' => true]);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $responseData['products']);
        $this->assertEquals($categoryId, $responseData['products'][0]['category_id']);
        $this->assertTrue($responseData['products'][0]['is_active']);
    }

    /** @test */
    public function get_products_with_updated_after_and_include_deleted(): void
    {
        $updatedAfter = Carbon::now()->subHour()->toIso8601String();
        $productId = $this->createTestUuid();
        $mockProductsData = [
            'products' => [
                ['id' => $productId, 'name' => 'Deleted Product', 'is_active' => false, 'deleted_at' => now()->toIso8601String(), 'updated_at' => now()->toIso8601String()],
            ],
            'pagination' => ['page' => 1, 'limit' => 1, 'total' => 1, 'has_next' => false],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/products', ['updated_after' => $updatedAfter, 'include_deleted' => true])
            ->andReturn(new Response(200, [], json_encode($mockProductsData)));

        $connector = $this->app->make(CommerceJsonConnector::class);
        $response = $connector->get('/catalog/products', ['updated_after' => $updatedAfter, 'include_deleted' => true]);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $responseData['products']);
        $this->assertEquals($productId, $responseData['products'][0]['id']);
        $this->assertFalse($responseData['products'][0]['is_active']);
        $this->assertNotNull($responseData['products'][0]['deleted_at']);
        $this->assertIso8601Date($responseData['products'][0]['deleted_at']);
    }

    /** @test */
    public function post_products_batch_upsert_success_and_idempotency(): void
    {
        $idempotencyKey = $this->createTestUuid();
        $categoryId = $this->createTestUuid();
        $productsData = [
            'products' => [
                [
                    'id' => $this->createTestUuid(),
                    'name' => 'Product A',
                    'category_id' => $categoryId,
                    'code' => 'PA001',
                    'is_active' => true,
                ],
                [
                    'id' => $this->createTestUuid(),
                    'name' => 'Product B',
                    'external_id' => 'EXT_PB001',
                    'category_id' => $categoryId,
                    'is_active' => true,
                ],
            ],
        ];
        $mockImportResult = [
            'success' => true,
            'processed' => 2,
            'errors' => [],
            'warnings' => [],
        ];

        // First call
        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/catalog/products', $productsData, $idempotencyKey)
            ->andReturn(new Response(200, [], json_encode($mockImportResult)));

        $connector = $this->app->make(CommerceJsonConnector::class);
        $response1 = $connector->post('/catalog/products', $productsData, $idempotencyKey);
        $responseData1 = json_decode((string) $response1->getBody(), true);

        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertTrue($responseData1['success']);
        $this->assertEquals(2, $responseData1['processed']);

        // Second call with the same idempotency key (should return cached result)
        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/catalog/products', $productsData, $idempotencyKey)
            ->andReturn(new Response(200, [], json_encode($mockImportResult))); // Assuming server returns 200 with same result

        $response2 = $connector->post('/catalog/products', $productsData, $idempotencyKey);
        $responseData2 = json_decode((string) $response2->getBody(), true);

        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertTrue($responseData2['success']);
        $this->assertEquals(2, $responseData2['processed']);
        $this->assertEquals($responseData1, $responseData2);
    }

    /** @test */
    public function post_products_throws_validation_exception_on_400(): void
    {
        $invalidProductsData = [
            'products' => [
                [
                    'id' => 'not-a-uuid', // Invalid UUID
                    'name' => '', // Empty name
                    'category_id' => $this->createTestUuid(),
                ],
            ],
        ];
        $errorResponse = [
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => ['products.0.id must be a valid UUID', 'products.0.name field is required'],
            ],
        ];

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/catalog/products', $invalidProductsData, null) // Corrected: Explicitly match invalidProductsData
            ->andThrow(new ValidationException(
                $errorResponse['error']['message'],
                $errorResponse['error']['details'],
                400
            ));

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(400);

        $this->mockConnector->post('/catalog/products', $invalidProductsData);
    }

    /** @test */
    public function get_product_by_id_returns_product(): void
    {
        $productId = $this->createTestUuid();
        $mockProductData = [
            'id' => $productId,
            'name' => 'Single Product',
            'code' => 'SP001',
            'category_id' => $this->createTestUuid(),
            'is_active' => true,
            'updated_at' => now()->toIso8601String(),
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/products/'.$productId, [])
            ->andReturn(new Response(200, [], json_encode($mockProductData)));

        $response = $this->mockConnector->get('/catalog/products/'.$productId);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($productId, $responseData['id']);
        $this->assertEquals('Single Product', $responseData['name']);
    }

    /** @test */
    public function get_product_by_id_throws_not_found_exception_on_404(): void
    {
        $nonExistentId = $this->createTestUuid();
        $errorResponse = [
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Product not found.',
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/products/'.$nonExistentId, [])
            ->andThrow(new \RuntimeException(
                $errorResponse['error']['message'],
                404
            ));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product not found.');

        $this->mockConnector->get('/catalog/products/'.$nonExistentId);
    }

    /** @test */
    public function delete_product_deactivates_product_successfully(): void
    {
        $productId = $this->createTestUuid();
        $mockProductData = [
            'id' => $productId,
            'name' => 'Product to be deleted',
            'is_active' => false,
            'deleted_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        $this->mockConnector->shouldReceive('delete')
            ->once()
            ->with('/catalog/products/'.$productId)
            ->andReturn(new Response(200, [], json_encode($mockProductData)));

        $response = $this->mockConnector->delete('/catalog/products/'.$productId);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($productId, $responseData['id']);
        $this->assertFalse($responseData['is_active']);
        $this->assertNotNull($responseData['deleted_at']);
        $this->assertIso8601Date($responseData['deleted_at']);
    }

    /** @test */
    public function delete_product_throws_not_found_exception_on_404(): void
    {
        $nonExistentId = $this->createTestUuid();
        $errorResponse = [
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Product not found for deletion.',
            ],
        ];

        $this->mockConnector->shouldReceive('delete')
            ->once()
            ->with('/catalog/products/'.$nonExistentId)
            ->andThrow(new ClientException(
                'Not Found',
                new Request('DELETE', '/catalog/products/'.$nonExistentId),
                new Response(404, [], json_encode($errorResponse))
            ));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product not found for deletion.');

        $this->mockConnector->delete('/catalog/products/'.$nonExistentId);
    }
}
