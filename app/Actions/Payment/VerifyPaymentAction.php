<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Models\Payment;
use App\Services\Payment\PaymentService;

final readonly class VerifyPaymentAction
{
    public function __construct(private PaymentService $service) {}

    public function handle(Payment $payment): Payment
    {
        return $this->service->verify($payment);
    }
}
