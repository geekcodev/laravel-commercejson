<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Data\CapabilitiesData;
use GeekCo\CommerceJson\Data\HandshakeResponseData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class HandshakeController extends Controller
{
    /**
     * Проверка соединения и параметры обмена.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $requestId = $request->header('X-Request-ID', (string) Str::uuid());

        $response = HandshakeResponseData::from([
            'version' => '1.0.8',
            'supported_versions' => ['1.0.8'],
            'server_time' => now()->toIso8601String(),
            'capabilities' => CapabilitiesData::from([
                'catalog' => true,
                'offers' => true,
                'orders' => true,
                'counterparties' => true,
                'warehouses' => true,
                'delta_sync' => true,
                'idempotency' => true,
                'max_page_size' => config('commercejson.exchange.batch_size.products', 100),
            ]),
        ]);

        return response()->json($response)->header('X-Request-ID', $requestId);
    }
}
