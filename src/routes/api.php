<?php

use GeekCo\CommerceJson\Http\Controllers\CategoryController;
use GeekCo\CommerceJson\Http\Controllers\CounterpartyController;
use GeekCo\CommerceJson\Http\Controllers\OfferController;
use GeekCo\CommerceJson\Http\Controllers\OrderController;
use GeekCo\CommerceJson\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/commercejson')->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('counterparties', CounterpartyController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('offers', OfferController::class);
});
