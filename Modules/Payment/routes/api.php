<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;

// Webhook endpoint cho gateway gọi vào (POST JSON, không CSRF).
Route::post('/payments/webhooks/{method}', [PaymentController::class, 'webhook'])
    ->name('payments.webhook');
