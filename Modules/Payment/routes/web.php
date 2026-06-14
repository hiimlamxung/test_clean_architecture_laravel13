<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;

Route::middleware('auth')->group(function (): void {
    Route::get('/orders/{order}/pay', [PaymentController::class, 'initiateForm'])->name('payments.initiate.form');
    Route::post('/orders/{order}/pay', [PaymentController::class, 'initiate'])->name('payments.initiate');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
});
