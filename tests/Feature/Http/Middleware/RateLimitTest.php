<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Enums\DocumentTypeEnum;

describe('Rate limiting on write routes', function () {
    it('allows requests within the default limit', function () {
        $commandBus = mockCommandBus();
        $data = test()->createOrderData();
        $commandBus->shouldReceive('dispatch')
            ->times(3)
            ->andReturn($data);

        $payload = [
            'document_type' => DocumentTypeEnum::Order->value,
            'items' => [['product_id' => test()->createTestUuid(), 'quantity' => 1]],
        ];

        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/commercejson/orders', $payload);
            expect($response->status())->not->toBe(429);
        }
    });

    it('returns 429 when rate limit is exceeded', function () {
        $commandBus = mockCommandBus();
        $data = test()->createOrderData();
        $commandBus->shouldReceive('dispatch')
            ->zeroOrMoreTimes()
            ->andReturn($data);

        $rateLimit = (int) config('commercejson.api_routes.rate_limit', 60);

        $payload = [
            'document_type' => DocumentTypeEnum::Order->value,
            'items' => [['product_id' => test()->createTestUuid(), 'quantity' => 1]],
        ];

        for ($i = 0; $i < $rateLimit; $i++) {
            $this->postJson('/api/commercejson/orders', $payload);
        }

        $response = $this->postJson('/api/commercejson/orders', $payload);

        expect($response->status())->toBe(429);
    });

    it('includes rate limit headers on exceeded limit', function () {
        $commandBus = mockCommandBus();
        $commandBus->shouldReceive('dispatch')
            ->zeroOrMoreTimes()
            ->andReturn(test()->createOrderData());

        $rateLimit = (int) config('commercejson.api_routes.rate_limit', 60);

        $payload = [
            'document_type' => DocumentTypeEnum::Order->value,
            'items' => [['product_id' => test()->createTestUuid(), 'quantity' => 1]],
        ];

        for ($i = 0; $i < $rateLimit; $i++) {
            $this->postJson('/api/commercejson/orders', $payload);
        }

        $response = $this->postJson('/api/commercejson/orders', $payload);

        expect($response->status())->toBe(429);
        expect($response->headers->has('X-RateLimit-Limit'))->toBeTrue();
        expect((int) $response->headers->get('X-RateLimit-Remaining'))->toBe(0);
    });

    it('does not rate limit GET requests', function () {
        $queryBus = mockQueryBus();
        $mockResult = Mockery::mock(stdClass::class);
        $mockResult->shouldReceive('items')->andReturn(collect([]));
        $mockResult->shouldReceive('currentPage')->andReturn(1);
        $mockResult->shouldReceive('lastPage')->andReturn(1);
        $mockResult->shouldReceive('perPage')->andReturn(15);
        $mockResult->shouldReceive('total')->andReturn(0);
        $queryBus->shouldReceive('ask')->andReturn($mockResult);

        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/commercejson/orders');
            expect($response->status())->not->toBe(429);
        }
    });
});
