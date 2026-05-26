<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

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

        // В соответствии со спецификацией OpenAPI, возвращаем HandshakeResponse
        // Здесь можно добавить логику для определения возможностей сервера,
        // но для начала вернем базовый ответ.
        return response()->json([
            'version' => '1.0.8', // Текущая версия CommerceJSON API
            'supported_versions' => ['1.0.8'],
            'server_time' => now()->toIso8601String(),
            'capabilities' => [
                'catalog' => true,
                'offers' => true,
                'orders' => true,
                'counterparties' => true,
                'warehouses' => true,
                'delta_sync' => true,
                'idempotency' => true,
                'max_page_size' => config('commercejson.exchange.batch_size.products', 100), // Пример, можно сделать более динамичным
            ],
            // 'session_token' => 'optional-session-token-if-used', // Если используется sessionAuth
        ])->header('X-Request-ID', $requestId);
    }
}
