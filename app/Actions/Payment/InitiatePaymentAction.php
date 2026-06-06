<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentService;

final readonly class InitiatePaymentAction
{
    public function __construct(private PaymentService $service) {}

    public function handle(Order $order, PaymentMethod $method): Payment
    {
        return $this->service->initiate($order, $method);
    }
}
