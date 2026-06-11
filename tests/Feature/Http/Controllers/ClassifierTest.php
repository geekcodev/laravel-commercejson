<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\PropertyTypeEnum;

describe('ClassifierController', function () {
    describe('GET /catalog/classifier', function () {
        it('returns empty classifier when no data exists', function () {
            $response = $this->getJson('/api/commercejson/catalog/classifier');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'name',
                    'version',
                    'categories',
                    'properties',
                    'price_types',
                    'updated_at',
                ])
                ->assertJson([
                    'categories' => [],
                    'properties' => [],
                    'price_types' => [],
                ]);
        });

        it('returns classifier with all data types', function () {
            $this->loadSeeders();

            $response = $this->getJson('/api/commercejson/catalog/classifier');

            $response->assertStatus(200);
            expect($response->json('id'))->not->toBeEmpty();
            expect($response->json('name'))->not->toBeEmpty();
            expect($response->json('categories'))->toBeArray();
            expect($response->json('properties'))->toBeArray();
            expect($response->json('price_types'))->toBeArray();
        });
    });

    describe('POST /catalog/classifier', function () {
        it('imports classifier data successfully', function () {
            $commandBus = mockCommandBus();
            $categoryId = test()->createTestUuid();
            $propertyId = test()->createTestUuid();
            $priceTypeId = test()->createTestUuid();

            $commandBus->shouldReceive('dispatch')
                ->times(3)
                ->andReturnValues([null, null, null]);

            $payload = [
                'id' => '00000000-0000-0000-0000-000000000001',
                'name' => 'Test Classifier',
                'version' => (string) now()->timestamp,
                'categories' => [
                    [
                        'id' => $categoryId,
                        'name' => 'Test Category',
                        'code' => 'CAT-001',
                    ],
                ],
                'properties' => [
                    [
                        'id' => $propertyId,
                        'name' => 'Color',
                        'type' => PropertyTypeEnum::String->value,
                    ],
                ],
                'price_types' => [
                    [
                        'id' => $priceTypeId,
                        'name' => 'Retail',
                        'currency' => CurrencyEnum::RUB->value,
                    ],
                ],
                'updated_at' => now()->toIso8601String(),
            ];

            $response = $this->postJson('/api/commercejson/catalog/classifier', $payload);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 3,
                    'errors' => [],
                    'warnings' => [],
                ]);
        });

        it('returns import errors on failure', function () {
            $commandBus = mockCommandBus();

            $commandBus->shouldReceive('dispatch')
                ->once()
                ->andThrow(new RuntimeException('Database error'));

            $payload = [
                'id' => '00000000-0000-0000-0000-000000000001',
                'name' => 'Test',
                'version' => '12345',
                'categories' => [
                    [
                        'id' => test()->createTestUuid(),
                        'name' => 'Failing Category',
                    ],
                ],
                'updated_at' => now()->toIso8601String(),
            ];

            $response = $this->postJson('/api/commercejson/catalog/classifier', $payload);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => false,
                    'processed' => 0,
                ]);
            expect($response->json('errors'))->toHaveCount(1);
        });
    });
});
