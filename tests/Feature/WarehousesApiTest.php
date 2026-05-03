<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature;

use GeekCo\CommerceJson\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;

class WarehousesApiTest extends TestCase
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
    public function get_warehouses_returns_list_with_deleted(): void
    {
        $warehouseId = $this->createTestUuid();
        $mockWarehousesData = [
            'warehouses' => [
                ['id' => $warehouseId, 'name' => 'Main Warehouse', 'code' => 'MAIN', 'is_active' => false, 'deleted_at' => now()->toIso8601String(), 'updated_at' => now()->toIso8601String()],
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/warehouses', ['include_deleted' => true])
            ->andReturn(new Response(200, [], json_encode($mockWarehousesData)));

        $response = $this->mockConnector->get('/warehouses', ['include_deleted' => true]);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $responseData['warehouses']);
        $this->assertEquals($warehouseId, $responseData['warehouses'][0]['id']);
        $this->assertFalse($responseData['warehouses'][0]['is_active']);
        $this->assertNotNull($responseData['warehouses'][0]['deleted_at']);
        $this->assertIso8601Date($responseData['warehouses'][0]['deleted_at']);
    }

    /** @test */
    public function post_warehouses_batch_import_success_and_idempotency(): void
    {
        $idempotencyKey = $this->createTestUuid();
        $warehousesData = [
            'warehouses' => [
                [
                    'id' => $this->createTestUuid(),
                    'name' => 'Imported Warehouse',
                    'code' => 'IMPWH',
                    'is_active' => true,
                ],
            ],
        ];
        $mockImportResult = ['success' => true, 'processed' => 1, 'errors' => [], 'warnings' => []];

        // First call
        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/warehouses', $warehousesData, $idempotencyKey)
            ->andReturn(new Response(200, [], json_encode($mockImportResult)));

        $response1 = $this->mockConnector->post('/warehouses', $warehousesData, $idempotencyKey);
        $responseData1 = json_decode((string) $response1->getBody(), true);

        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertTrue($responseData1['success']);

        // Second call with the same idempotency key
        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/warehouses', $warehousesData, $idempotencyKey)
            ->andReturn(new Response(200, [], json_encode($mockImportResult)));

        $response2 = $this->mockConnector->post('/warehouses', $warehousesData, $idempotencyKey);
        $responseData2 = json_decode((string) $response2->getBody(), true);

        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertTrue($responseData2['success']);
        $this->assertEquals($responseData1, $responseData2);
    }

    /** @test */
    public function post_warehouses_throws_validation_exception_for_missing_name(): void
    {
        $invalidWarehousesData = [
            'warehouses' => [
                [
                    'id' => $this->createTestUuid(),
                    'code' => 'INVWH',
                    // Missing name
                ],
            ],
        ];
        $errorResponse = [
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => ['warehouses.0.name field is required.'],
            ],
        ];

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/warehouses', $invalidWarehousesData, null) // Corrected: Explicitly match invalidWarehousesData
            ->andThrow(new ValidationException(
                $errorResponse['error']['message'],
                $errorResponse['error']['details'],
                400
            ));

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(400);

        $this->mockConnector->post('/warehouses', $invalidWarehousesData);
    }
}
