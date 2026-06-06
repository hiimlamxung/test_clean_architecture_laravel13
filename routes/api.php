<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Enums\PaymentMethod;

Route::get('/test', function () {
    dd(PaymentMethod::from('Momo'));
});

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('user', fn (Request $request) => $request->user());

    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);

    Route::post('payments/initiate', [PaymentController::class, 'initiate']);
    Route::get('payments/{payment}', [PaymentController::class, 'show']);
});

Route::post('payments/webhooks/{method}', [PaymentController::class, 'webhook']);
