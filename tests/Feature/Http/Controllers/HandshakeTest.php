<?php

declare(strict_types=1);

describe('HandshakeController', function () {
    it('returns handshake response with server info', function () {
        $response = $this->getJson('/api/commercejson/handshake');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'version',
                'supported_versions',
                'server_time',
                'capabilities' => [
                    'catalog',
                    'offers',
                    'orders',
                    'counterparties',
                    'warehouses',
                    'delta_sync',
                    'idempotency',
                    'max_page_size',
                ],
            ])
            ->assertJson([
                'version' => '1.0.8',
                'supported_versions' => ['1.0.8'],
            ])
            ->assertHeader('X-Request-ID');
    });

    it('responds with unique X-Request-ID per request', function () {
        $first = $this->getJson('/api/commercejson/handshake');
        $second = $this->getJson('/api/commercejson/handshake');

        expect($first->headers->get('X-Request-ID'))->not->toBe(
            $second->headers->get('X-Request-ID')
        );
    });
});
