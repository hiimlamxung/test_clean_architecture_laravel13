<?php

declare(strict_types=1);

namespace Modules\Payment\Actions;

use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;

final readonly class InitiatePaymentAction
{
    public function __construct(private PaymentService $service) {}

    public function handle(Order $order, PaymentMethod $method): Payment
    {
        return $this->service->initiate($order, $method);
    }
}
