<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Monolog\Handler\TestHandler;

describe('ApiLoggingMiddleware', function () {
    beforeEach(function () {
        $this->testHandler = new TestHandler;

        Log::channel('commercejson-api')->pushHandler($this->testHandler);
    });

    it('logs incoming GET request and response', function () {
        $queryBus = mockQueryBus();
        $queryBus->shouldReceive('ask')->once()->andReturn(
            Mockery::mock(['items' => collect([]), 'currentPage' => 1, 'lastPage' => 1, 'perPage' => 15, 'total' => 0])
        );

        $this->getJson('/api/commercejson/catalog/products');

        $records = $this->testHandler->getRecords();
        $messages = array_map(fn ($r) => $r['message'], $records);

        expect($messages)->toContain('Incoming API request');
        expect($messages)->toContain('API response');
    });

    it('includes method and url in request log', function () {
        $queryBus = mockQueryBus();
        $queryBus->shouldReceive('ask')->once()->andReturn(
            Mockery::mock(['items' => collect([]), 'currentPage' => 1, 'lastPage' => 1, 'perPage' => 15, 'total' => 0])
        );

        $this->getJson('/api/commercejson/catalog/products');

        $records = $this->testHandler->getRecords();
        $requestRecord = collect($records)->firstWhere('message', 'Incoming API request');

        expect($requestRecord['context']['method'])->toBe('GET');
        expect($requestRecord['context']['url'])->toContain('/api/commercejson/catalog/products');
    });

    it('includes status and duration in response log', function () {
        $queryBus = mockQueryBus();
        $queryBus->shouldReceive('ask')->once()->andReturn(
            Mockery::mock(['items' => collect([]), 'currentPage' => 1, 'lastPage' => 1, 'perPage' => 15, 'total' => 0])
        );

        $this->getJson('/api/commercejson/catalog/products');

        $records = $this->testHandler->getRecords();
        $responseRecord = collect($records)->firstWhere('message', 'API response');

        expect($responseRecord['context']['status'])->toBe(200);
        expect($responseRecord['context'])->toHaveKey('duration_ms');
    });

    it('logs POST request body', function () {
        $commandBus = mockCommandBus();
        $orderData = test()->createOrderData();
        $commandBus->shouldReceive('dispatch')
            ->once()
            ->andReturn($orderData);

        $payload = [
            'document_type' => 'order',
            'items' => [
                ['product_id' => (string) Str::uuid(), 'quantity' => 1],
            ],
        ];

        $this->postJson('/api/commercejson/orders', $payload);

        $records = $this->testHandler->getRecords();
        $requestRecord = collect($records)->firstWhere('message', 'Incoming API request');

        expect($requestRecord['context']['body']['document_type'])->toBe('order');
    });

    it('logs 4xx response as warning', function () {
        $queryBus = mockQueryBus();
        $queryBus->shouldReceive('ask')
            ->once()
            ->andThrow(new ModelNotFoundException);

        $this->getJson('/api/commercejson/catalog/products/'.(string) Str::uuid());

        $records = $this->testHandler->getRecords();
        $responseRecords = collect($records)->where('message', 'API response');

        expect($responseRecords)->toHaveCount(1);
        $responseRecord = $responseRecords->first();
        expect($responseRecord['level'])->toBe(300);
        expect($responseRecord['context']['status'])->toBe(404);
    });

    it('masks sensitive data in request body', function () {
        $commandBus = mockCommandBus();
        $commandBus->shouldReceive('dispatch')
            ->once()
            ->andReturn(test()->createOrderData());

        $payload = [
            'auth_token' => 'super-secret',
            'password' => 'hunter2',
            'document_type' => 'order',
            'items' => [
                ['product_id' => (string) Str::uuid(), 'quantity' => 1],
            ],
        ];

        $this->postJson('/api/commercejson/orders', $payload);

        $records = $this->testHandler->getRecords();
        $requestRecord = collect($records)->firstWhere('message', 'Incoming API request');

        expect($requestRecord['context']['body']['auth_token'])->toBe('***');
        expect($requestRecord['context']['body']['password'])->toBe('***');
        expect($requestRecord['context']['body']['document_type'])->toBe('order');
    });

    describe('with logging disabled', function () {
        beforeEach(function () {
            mockQueryBus()->shouldReceive('ask')
                ->andReturn(Mockery::mock(['items' => collect([]), 'currentPage' => 1, 'lastPage' => 1, 'perPage' => 15, 'total' => 0]));
        });

        it('does not log when api_logging is disabled', function () {
            config()->set('commercejson.api_logging.enabled', false);

            $this->getJson('/api/commercejson/catalog/products');

            expect($this->testHandler->getRecords())->toBeEmpty();
        });

        it('does not log excluded paths', function () {
            config()->set('commercejson.api_logging.exclude_paths', ['products']);

            $this->getJson('/api/commercejson/catalog/products');

            expect($this->testHandler->getRecords())->toBeEmpty();
        });
    });

    it('excludes request body for matching paths but still logs metadata', function () {
        mockCommandBus()->shouldReceive('dispatch')
            ->zeroOrMoreTimes();

        config()->set('commercejson.api_logging.exclude_request_body_paths', ['orders']);

        $this->postJson('/api/commercejson/orders', ['document_type' => 'order']);

        $records = $this->testHandler->getRecords();
        $requestRecord = collect($records)->firstWhere('message', 'Incoming API request');

        expect($requestRecord['context'])->toHaveKey('method');
        expect($requestRecord['context'])->toHaveKey('url');
        expect($requestRecord['context'])->not->toHaveKey('body');
    });

    it('excludes response body for matching paths but still logs metadata', function () {
        mockCommandBus()->shouldReceive('dispatch')
            ->zeroOrMoreTimes();

        config()->set('commercejson.api_logging.log_response_body', true);
        config()->set('commercejson.api_logging.exclude_response_body_paths', ['orders']);

        $this->postJson('/api/commercejson/orders', ['document_type' => 'order']);

        $records = $this->testHandler->getRecords();
        $responseRecord = collect($records)->firstWhere('message', 'API response');

        expect($responseRecord['context'])->toHaveKey('status');
        expect($responseRecord['context'])->toHaveKey('duration_ms');
        expect($responseRecord['context'])->not->toHaveKey('body');
    });

    it('includes request body when path is not excluded', function () {
        mockCommandBus()->shouldReceive('dispatch')
            ->zeroOrMoreTimes();

        config()->set('commercejson.api_logging.exclude_request_body_paths', ['products']);

        $this->postJson('/api/commercejson/orders', ['document_type' => 'order']);

        $records = $this->testHandler->getRecords();
        $requestRecord = collect($records)->firstWhere('message', 'Incoming API request');

        expect($requestRecord['context']['body']['document_type'])->toBe('order');
    });

    it('includes response body when path is not excluded', function () {
        mockCommandBus()->shouldReceive('dispatch')
            ->zeroOrMoreTimes();

        config()->set('commercejson.api_logging.log_response_body', true);
        config()->set('commercejson.api_logging.exclude_response_body_paths', ['products']);

        $this->postJson('/api/commercejson/orders', ['document_type' => 'order']);

        $records = $this->testHandler->getRecords();
        $responseRecord = collect($records)->firstWhere('message', 'API response');

        expect($responseRecord['context']['body'])->not->toBeNull();
    });
});
