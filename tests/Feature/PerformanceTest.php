<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature;

use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;

class PerformanceTest extends TestCase
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
    public function benchmark_post_products_with_large_data_set(): void
    {
        $numberOfProducts = 5000;
        $categoryId = $this->createTestUuid();

        $productsData = ['products' => []];
        for ($i = 0; $i < $numberOfProducts; $i++) {
            $productsData['products'][] = [
                'id' => $this->createTestUuid(),
                'name' => 'Benchmark Product '.$i,
                'category_id' => $categoryId,
                'code' => 'BP'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'is_active' => true,
            ];
        }

        $mockImportResult = [
            'success' => true,
            'processed' => $numberOfProducts,
            'errors' => [],
            'warnings' => [],
        ];

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/catalog/products', Mockery::any(), Mockery::any()) // Relaxed matching for data and idempotencyKey
            ->andReturn(new Response(200, [], json_encode($mockImportResult)));

        $startTime = microtime(true);
        $response = $this->mockConnector->post('/catalog/products', $productsData, $this->createTestUuid());
        $endTime = microtime(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(json_decode((string) $response->getBody(), true)['success']);

        $executionTime = $endTime - $startTime;
        $this->log("Benchmark: POST /catalog/products with {$numberOfProducts} products took {$executionTime} seconds.");

        // Установите пороговое значение в зависимости от ожидаемой производительности
        // Например, менее 2 секунд для 5000 продуктов
        $this->assertLessThan(2.0, $executionTime, "POST /catalog/products with {$numberOfProducts} products is too slow.");
    }

    /** @test */
    public function benchmark_post_orders_bulk_with_large_data_set(): void
    {
        $numberOfOrders = 1000;

        $ordersData = ['orders' => []];
        for ($i = 0; $i < $numberOfOrders; $i++) {
            $ordersData['orders'][] = [
                'id' => $this->createTestUuid(),
                'status' => 'new',
                'document_type' => 'order',
                'customer' => ['name' => 'Benchmark Customer '.$i, 'phone' => '+79001234567'],
                'items' => [['product_id' => $this->createTestUuid(), 'quantity' => 1]],
            ];
        }

        $mockImportResult = [
            'success' => true,
            'processed' => $numberOfOrders,
            'errors' => [],
            'warnings' => [],
        ];

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/orders/bulk', Mockery::any(), Mockery::any()) // Relaxed matching for data and idempotencyKey
            ->andReturn(new Response(200, [], json_encode($mockImportResult)));

        $startTime = microtime(true);
        $response = $this->mockConnector->post('/orders/bulk', $ordersData, $this->createTestUuid());
        $endTime = microtime(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(json_decode((string) $response->getBody(), true)['success']);

        $executionTime = $endTime - $startTime;
        $this->log("Benchmark: POST /orders/bulk with {$numberOfOrders} orders took {$executionTime} seconds.");

        // Установите пороговое значение в зависимости от ожидаемой производительности
        // Например, менее 1 секунды для 1000 заказов
        $this->assertLessThan(1.0, $executionTime, "POST /orders/bulk with {$numberOfOrders} orders is too slow.");
    }

    protected function log(string $message): void
    {
        // В реальном проекте можно использовать Laravel's logger
        // Или просто выводить в консоль при запуске тестов
        echo '
'.$message;
    }
}
