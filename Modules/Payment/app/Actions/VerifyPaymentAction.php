<?php

declare(strict_types=1);

namespace Modules\Payment\Actions;

use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;

final readonly class VerifyPaymentAction
{
    public function __construct(private PaymentService $service) {}

    public function handle(Payment $payment): Payment
    {
        return $this->service->verify($payment);
    }
}
