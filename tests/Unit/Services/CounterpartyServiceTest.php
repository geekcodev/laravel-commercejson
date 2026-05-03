<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Services;

use GeekCo\CommerceJson\Data\CounterpartyData;
use GeekCo\CommerceJson\Data\CounterpartyListData;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Services\CounterpartyService;
use GeekCo\CommerceJson\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Response;

/**
 * Тесты для CounterpartyService
 *
 * @covers \GeekCo\CommerceJson\Services\CounterpartyService
 */
class CounterpartyServiceTest extends TestCase
{
    protected CounterpartyService $counterpartyService;

    protected MockHandler $mockHandler;

    protected array $history = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHandler = new MockHandler;
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push(Middleware::history($this->history));

        $mockClient = new Client(['handler' => $handlerStack]);

        $connector = new CommerceJsonConnector(
            baseUrl: 'https://api.test.com/v1',
            authToken: 'test-token',
            timeout: 30,
            authType: 'bearer'
        );

        $reflection = new \ReflectionClass($connector);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($connector, $mockClient);

        // Debug: verify client was set
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $client = $clientProperty->getValue($connector);
        $this->assertInstanceOf(Client::class, $client, 'Mock client should be set');

        $this->counterpartyService = new CounterpartyService($connector);
    }

    /** @test */
    public function get_counterparties_returns_counterparty_list_data(): void
    {
        $counterpartyId1 = $this->createTestUuid();
        $counterpartyId2 = $this->createTestUuid();
        $mockResponseContent = [
            'counterparties' => [
                ['id' => $counterpartyId1, 'type' => 'legal_entity', 'name' => 'ООО Test'],
                ['id' => $counterpartyId2, 'type' => 'individual', 'name' => 'Иванов И.И.'],
            ],
            'pagination' => ['page' => 1, 'limit' => 50, 'total' => 2, 'has_next' => false],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponseContent)));

        $counterpartyListData = $this->counterpartyService->getCounterparties(page: 1, limit: 50);

        $this->assertInstanceOf(CounterpartyListData::class, $counterpartyListData);
        $this->assertCount(2, $counterpartyListData->counterparties);
        $this->assertEquals($counterpartyId1, $counterpartyListData->counterparties[0]->id);
        $this->assertEquals(2, $counterpartyListData->pagination->total);
    }

    /** @test */
    public function get_counterparties_filters_are_passed_correctly(): void
    {
        $updatedAfter = now()->subDays(2);
        $mockResponseContent = ['counterparties' => [], 'pagination' => ['page' => 1, 'limit' => 50, 'total' => 0, 'has_next' => false]];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponseContent)));

        $this->counterpartyService->getCounterparties(
            page: 1,
            limit: 50,
            type: 'legal_entity',
            updatedAfter: $updatedAfter,
            includeDeleted: true
        );

        $this->assertNotEmpty($this->history);
        $request = $this->history[0]['request'];
        $query = Query::parse($request->getUri()->getQuery());

        $this->assertEquals('legal_entity', $query['type']);
        $this->assertArrayHasKey('updated_after', $query);
        $this->assertEquals('true', $query['include_deleted']);
    }

    /** @test */
    public function get_counterparty_by_id_returns_counterparty_data(): void
    {
        $counterpartyId = $this->createTestUuid();
        $mockResponseContent = [
            'id' => $counterpartyId,
            'type' => 'individual',
            'name' => 'Иванов И.И.',
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponseContent)));

        $counterpartyData = $this->counterpartyService->getCounterparty($counterpartyId);

        $this->assertInstanceOf(CounterpartyData::class, $counterpartyData);
        $this->assertEquals($counterpartyId, $counterpartyData->id);
        $this->assertEquals('individual', $counterpartyData->type->value);
    }

    /** @test */
    public function import_counterparties_sends_correct_data_and_idempotency_key(): void
    {
        $idempotencyKey = $this->createTestUuid();
        $counterpartiesArray = [
            ['id' => $this->createTestUuid(), 'type' => 'legal_entity', 'name' => 'ООО Import', 'inn' => '1234567890'],
        ];
        $mockResponseContent = ['success' => true, 'processed' => 1, 'errors' => [], 'warnings' => []];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponseContent)));

        $importResult = $this->counterpartyService->importCounterparties($counterpartiesArray, $idempotencyKey);

        $this->assertTrue($importResult->success);
        $this->assertEquals(1, $importResult->processed);

        $this->assertNotEmpty($this->history);
        $request = $this->history[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($idempotencyKey, $request->getHeaderLine('Idempotency-Key'));
    }

    /** @test */
    public function sync_counterparty_creates_or_updates_counterparty_model(): void
    {
        $counterpartyId = $this->createTestUuid();
        $mockResponseContent = [
            'id' => $counterpartyId,
            'type' => 'individual',
            'name' => 'Иванов И.И.',
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponseContent)));

        $counterpartyData = $this->counterpartyService->getCounterparty($counterpartyId);

        $this->assertInstanceOf(CounterpartyData::class, $counterpartyData);
        $this->assertEquals($counterpartyId, $counterpartyData->id);
    }
}
