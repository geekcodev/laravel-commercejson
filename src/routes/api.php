<?php

use GeekCo\CommerceJson\Http\Controllers\ClassifierController;
use GeekCo\CommerceJson\Http\Controllers\CounterpartyController;
use GeekCo\CommerceJson\Http\Controllers\HandshakeController;
use GeekCo\CommerceJson\Http\Controllers\OfferController;
use GeekCo\CommerceJson\Http\Controllers\OrderController;
use GeekCo\CommerceJson\Http\Controllers\ProductController;
use GeekCo\CommerceJson\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('commercejson.api_routes.prefix'))
    ->middleware('commercejson.log')
    ->group(function () {
        Route::get('handshake', HandshakeController::class);

        $authMiddleware = config('commercejson.api_routes.middleware');
        $idempotencyMiddleware = 'commercejson.idempotency';

        $rateLimit = config('commercejson.api_routes.rate_limit');
        if ($rateLimit !== null && $rateLimit !== false) {
            $decay = config('commercejson.api_routes.rate_limit_decay', 1);
            $authMiddleware[] = "throttle:{$rateLimit},{$decay}";
        }

        Route::middleware($authMiddleware)->group(function () use ($idempotencyMiddleware) {
            // ── Catalog / Classifier ───────────────────────────
            Route::prefix('catalog')->group(function () use ($idempotencyMiddleware) {
                Route::get('classifier', [ClassifierController::class, 'index']);
                Route::post('classifier', [ClassifierController::class, 'store'])
                    ->middleware($idempotencyMiddleware);

                Route::get('products', [ProductController::class, 'index']);
                Route::post('products', [ProductController::class, 'store'])
                    ->middleware($idempotencyMiddleware);
                Route::get('products/{id}', [ProductController::class, 'show']);
                Route::delete('products/{id}', [ProductController::class, 'destroy']);
            });

            // ── Offers ─────────────────────────────────────────
            Route::get('offers', [OfferController::class, 'index']);
            Route::post('offers', [OfferController::class, 'store'])
                ->middleware($idempotencyMiddleware);
            Route::get('offers/price-types', [OfferController::class, 'priceTypes']);

            // ── Orders ─────────────────────────────────────────
            Route::get('orders', [OrderController::class, 'index']);
            Route::post('orders', [OrderController::class, 'store'])
                ->middleware($idempotencyMiddleware);
            Route::post('orders/bulk', [OrderController::class, 'bulkUpdate'])
                ->middleware($idempotencyMiddleware);
            Route::get('orders/{id}', [OrderController::class, 'show']);
            Route::patch('orders/{id}', [OrderController::class, 'update'])
                ->middleware($idempotencyMiddleware);

            // ── Counterparties ─────────────────────────────────
            Route::get('counterparties', [CounterpartyController::class, 'index']);
            Route::post('counterparties', [CounterpartyController::class, 'store'])
                ->middleware($idempotencyMiddleware);
            Route::get('counterparties/{id}', [CounterpartyController::class, 'show']);

            // ── Warehouses ─────────────────────────────────────
            Route::get('warehouses', [WarehouseController::class, 'index']);
            Route::post('warehouses', [WarehouseController::class, 'store'])
                ->middleware($idempotencyMiddleware);
        });
    });
