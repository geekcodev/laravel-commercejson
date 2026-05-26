<?php

use GeekCo\CommerceJson\Http\Controllers\CategoryController;
use GeekCo\CommerceJson\Http\Controllers\CounterpartyController;
use GeekCo\CommerceJson\Http\Controllers\HandshakeController;
use GeekCo\CommerceJson\Http\Controllers\OfferController;
use GeekCo\CommerceJson\Http\Controllers\OrderController;
use GeekCo\CommerceJson\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('commercejson.api_routes.prefix'))->group(function () {
    // Маршрут для handshake без middleware
    Route::get('handshake', HandshakeController::class);

    // Группа маршрутов с middleware для остальных API ресурсов
    Route::middleware(config('commercejson.api_routes.middleware'))->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::apiResource('orders', OrderController::class);
        Route::apiResource('counterparties', CounterpartyController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('offers', OfferController::class);
    });
});
