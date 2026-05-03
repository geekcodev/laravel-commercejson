<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Feature;

use Carbon\Carbon;
use GeekCo\CommerceJson\Exceptions\BusinessException;
use GeekCo\CommerceJson\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;

class ClassifierApiTest extends TestCase
{
    /**
     * @var MockInterface|CommerceJsonConnector
     */
    protected $mockConnector;

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
    public function get_classifier_returns_success_response(): void
    {
        $mockClassifierData = [
            'id' => $this->createTestUuid(),
            'name' => 'Main Classifier',
            'version' => 'v1.0.8',
            'categories' => [],
            'properties' => [],
            'price_types' => [],
            'updated_at' => now()->toIso8601String(),
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/classifier', [])
            ->andReturn(new Response(200, [], json_encode($mockClassifierData)));

        $response = $this->mockConnector->get('/catalog/classifier', []);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($mockClassifierData['name'], $responseData['name']);
        $this->assertValidUuid($responseData['id']);
    }

    /** @test */
    public function get_classifier_with_updated_after_filter(): void
    {
        $updatedAfter = Carbon::now()->subDay()->toIso8601String();
        $mockClassifierData = [
            'id' => $this->createTestUuid(),
            'name' => 'Updated Classifier',
            'updated_at' => now()->toIso8601String(),
        ];

        $this->mockConnector->shouldReceive('get')
            ->once()
            ->with('/catalog/classifier', ['updated_after' => $updatedAfter])
            ->andReturn(new Response(200, [], json_encode($mockClassifierData)));

        $response = $this->mockConnector->get('/catalog/classifier', ['updated_after' => $updatedAfter]);
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($mockClassifierData['name'], $responseData['name']);
        $this->assertIso8601Date($responseData['updated_at']);
    }

    /** @test */
    public function post_classifier_success_and_idempotency(): void
    {
        $idempotencyKey = $this->createTestUuid();
        $classifierData = [
            'id' => $this->createTestUuid(),
            'name' => 'New Classifier',
            'version' => 'v1.0.9',
            'categories' => [['id' => $this->createTestUuid(), 'name' => 'Category A']],
            'properties' => [],
            'price_types' => [],
        ];
        $mockImportResult = [
            'success' => true,
            'processed' => 1,
            'errors' => [],
            'warnings' => [],
        ];

        // First call
        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/catalog/classifier', $classifierData, $idempotencyKey)
            ->andReturn(new Response(200, [], json_encode($mockImportResult)));

        $response1 = $this->mockConnector->post('/catalog/classifier', $classifierData, $idempotencyKey);
        $responseData1 = json_decode((string) $response1->getBody(), true);

        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertTrue($responseData1['success']);

        // Second call with the same idempotency key (should return cached result)
        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/catalog/classifier', $classifierData, $idempotencyKey)
            ->andReturn(new Response(200, [], json_encode($mockImportResult))); // Assuming server returns 200 with same result

        $response2 = $this->mockConnector->post('/catalog/classifier', $classifierData, $idempotencyKey);
        $responseData2 = json_decode((string) $response2->getBody(), true);

        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertTrue($responseData2['success']);
        $this->assertEquals($responseData1, $responseData2);
    }

    /** @test */
    public function post_classifier_throws_validation_exception_on_400(): void
    {
        $invalidClassifierData = [
            'id' => 'not-a-uuid', // Invalid UUID
            'name' => '', // Empty name
        ];
        $errorResponse = [
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => ['id must be a valid UUID', 'name field is required'],
            ],
        ];

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/catalog/classifier', $invalidClassifierData, null)
            ->andThrow(new ValidationException(
                $errorResponse['error']['message'],
                $errorResponse['error']['details'],
                400
            ));

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(400);

        $this->mockConnector->post('/catalog/classifier', $invalidClassifierData);
    }

    /** @test */
    public function post_classifier_throws_business_exception_on_422(): void
    {
        $classifierData = [
            'id' => $this->createTestUuid(),
            'name' => 'Classifier with Business Rule Error',
            'version' => 'v1.0.8',
            'categories' => [],
            'properties' => [],
            'price_types' => [],
        ];
        $errorResponse = [
            'error' => [
                'code' => 'CLASSIFIER_BUSINESS_RULE_ERROR',
                'message' => 'Classifier cannot be updated due to active products.',
                'details' => [],
            ],
        ];

        $this->mockConnector->shouldReceive('post')
            ->once()
            ->with('/catalog/classifier', $classifierData, null)
            ->andThrow(new BusinessException(
                $errorResponse['error']['message'],
                $errorResponse['error']['code'],
                422
            ));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(422);

        $this->mockConnector->post('/catalog/classifier', $classifierData);
    }
}
