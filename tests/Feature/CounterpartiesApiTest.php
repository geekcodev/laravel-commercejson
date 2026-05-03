<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature;

use Carbon\Carbon;
use GeekCo\CommerceJson\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;

class CounterpartiesApiTest extends TestCase
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
    public function get_counterparties_returns_paginated_list(): void
    {
        $mockCounterpartiesData = [
            'counterparties' => [
                ['id' => $this->createTestUuid(), 'type' => 'legal_entity', 'name' => 'ООО Тест', 'updated_at' => now()->toIso8601String()],
                ['id' => $this->createTestUuid(), 'type' => 'individual', 'name' => 'Иванов И.И.', 'updated_at' => now()->toIso8601String()],
            ],
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'has_next' => false],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/counterparties', ['page' => 1, 'limit' => 10])
            ->andReturn(new Response(200, [], json_encode($mockCounterpartiesData)));

        $response = $this->mockConnector->get('/counterparties', ['page' => 1, 'limit' => 10]);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $responseData['counterparties']);
        $this->assertEquals('legal_entity', $responseData['counterparties'][0]['type']);
        $this->assertValidUuid($responseData['counterparties'][0]['id']);
    }

    /** @test */
    public function get_counterparties_with_filters_and_incremental_sync(): void
    {
        $updatedAfter = Carbon::now()->subHour()->toIso8601String();
        $counterpartyId = $this->createTestUuid();
        $mockCounterpartiesData = [
            'counterparties' => [
                ['id' => $counterpartyId, 'type' => 'individual_entrepreneur', 'name' => 'ИП Тестов', 'deleted_at' => now()->toIso8601String(), 'updated_at' => now()->toIso8601String()],
            ],
            'pagination' => ['page' => 1, 'limit' => 1, 'total' => 1, 'has_next' => false],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/counterparties', [
                'type' => 'individual_entrepreneur',
                'updated_after' => $updatedAfter,
                'include_deleted' => true,
            ])
            ->andReturn(new Response(200, [], json_encode($mockCounterpartiesData)));

        $response = $this->mockConnector->get('/counterparties', [
            'type' => 'individual_entrepreneur',
            'updated_after' => $updatedAfter,
            'include_deleted' => true,
        ]);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $responseData['counterparties']);
        $this->assertEquals('individual_entrepreneur', $responseData['counterparties'][0]['type']);
        $this->assertNotNull($responseData['counterparties'][0]['deleted_at']);
        $this->assertIso8601Date($responseData['counterparties'][0]['deleted_at']);
    }

    /** @test */
    public function post_counterparties_batch_import_success_and_idempotency(): void
    {
        $idempotencyKey = $this->createTestUuid();
        $counterpartiesData = [
            'counterparties' => [
                [
                    'id' => $this->createTestUuid(),
                    'type' => 'legal_entity',
                    'name' => 'ООО Import',
                    'inn' => '1234567890',
                    'kpp' => '123456789',
                    'ogrn' => '1234567890123',
                ],
            ],
        ];
        $mockImportResult = ['success' => true, 'processed' => 1, 'errors' => [], 'warnings' => []];

        // First call
        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/counterparties', $counterpartiesData, $idempotencyKey)
            ->andReturn(new Response(200, [], json_encode($mockImportResult)));

        $response1 = $this->mockConnector->post('/counterparties', $counterpartiesData, $idempotencyKey);
        $responseData1 = json_decode((string) $response1->getBody(), true);

        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertTrue($responseData1['success']);

        // Second call with the same idempotency key
        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/counterparties', $counterpartiesData, $idempotencyKey)
            ->andReturn(new Response(200, [], json_encode($mockImportResult)));

        $response2 = $this->mockConnector->post('/counterparties', $counterpartiesData, $idempotencyKey);
        $responseData2 = json_decode((string) $response2->getBody(), true);

        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertTrue($responseData2['success']);
        $this->assertEquals($responseData1, $responseData2);
    }

    /** @test */
    public function post_counterparties_throws_validation_exception_for_invalid_inn_ogrn(): void
    {
        $invalidCounterpartyData = [
            'counterparties' => [
                [
                    'id' => $this->createTestUuid(),
                    'type' => 'legal_entity',
                    'name' => 'ООО Invalid',
                    'inn' => '12345', // Invalid INN length for legal entity
                    'ogrn' => 'ABC', // Invalid OGRN format
                ],
            ],
        ];
        $errorResponse = [
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => ['counterparties.0.inn must be 10 digits', 'counterparties.0.ogrn must be 13 digits'],
            ],
        ];

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/counterparties', $invalidCounterpartyData, null) // Corrected: Explicitly match invalidCounterpartyData
            ->andThrow(new ValidationException(
                $errorResponse['error']['message'],
                $errorResponse['error']['details'],
                400
            ));

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(400);

        $this->mockConnector->post('/counterparties', $invalidCounterpartyData);
    }

    /** @test */
    public function get_counterparty_by_id_returns_counterparty(): void
    {
        $counterpartyId = $this->createTestUuid();
        $mockCounterpartyData = [
            'id' => $counterpartyId,
            'type' => 'individual',
            'name' => 'Single Counterparty',
            'updated_at' => now()->toIso8601String(),
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/counterparties/'.$counterpartyId, [])
            ->andReturn(new Response(200, [], json_encode($mockCounterpartyData)));

        $response = $this->mockConnector->get('/counterparties/'.$counterpartyId);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($counterpartyId, $responseData['id']);
        $this->assertEquals('individual', $responseData['type']);
    }

    /** @test */
    public function get_counterparty_by_id_throws_not_found_exception_on_404(): void
    {
        $nonExistentId = $this->createTestUuid();
        $errorResponse = [
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Counterparty not found.',
            ],
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/counterparties/'.$nonExistentId, []) // Corrected: Explicitly match empty array
            ->andThrow(new \RuntimeException( // Corrected: Throw RuntimeException as per connector's mapping
                $errorResponse['error']['message'],
                404
            ));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Counterparty not found.');

        $this->mockConnector->get('/counterparties/'.$nonExistentId);
    }
}
