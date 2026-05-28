<?php

use GeekCo\CommerceJson\Http\Controllers\ClassifierController;
use GeekCo\CommerceJson\Http\Controllers\CounterpartyController;
use GeekCo\CommerceJson\Http\Controllers\HandshakeController;
use GeekCo\CommerceJson\Http\Controllers\OfferController;
use GeekCo\CommerceJson\Http\Controllers\OrderController;
use GeekCo\CommerceJson\Http\Controllers\ProductController;
use GeekCo\CommerceJson\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('commercejson.api_routes.prefix'))->group(function () {
    Route::get('handshake', HandshakeController::class);

    Route::middleware(config('commercejson.api_routes.middleware'))->group(function () {
        // ── Catalog / Classifier ───────────────────────────
        Route::prefix('catalog')->group(function () {
            Route::get('classifier', [ClassifierController::class, 'index']);
            Route::post('classifier', [ClassifierController::class, 'store']);

            Route::get('products', [ProductController::class, 'index']);
            Route::post('products', [ProductController::class, 'store']);
            Route::get('products/{id}', [ProductController::class, 'show']);
            Route::delete('products/{id}', [ProductController::class, 'destroy']);
        });

        // ── Offers ─────────────────────────────────────────
        Route::get('offers', [OfferController::class, 'index']);
        Route::post('offers', [OfferController::class, 'store']);
        Route::get('offers/price-types', [OfferController::class, 'priceTypes']);

        // ── Orders ─────────────────────────────────────────
        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::post('orders/bulk', [OrderController::class, 'bulkUpdate']);
        Route::get('orders/{id}', [OrderController::class, 'show']);
        Route::patch('orders/{id}', [OrderController::class, 'update']);

        // ── Counterparties ─────────────────────────────────
        Route::get('counterparties', [CounterpartyController::class, 'index']);
        Route::post('counterparties', [CounterpartyController::class, 'store']);
        Route::get('counterparties/{id}', [CounterpartyController::class, 'show']);

        // ── Warehouses ─────────────────────────────────────
        Route::get('warehouses', [WarehouseController::class, 'index']);
        Route::post('warehouses', [WarehouseController::class, 'store']);
    });
});
