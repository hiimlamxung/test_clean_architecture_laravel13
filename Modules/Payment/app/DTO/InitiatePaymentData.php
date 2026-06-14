<?php

declare(strict_types=1);

namespace Modules\Payment\DTO;

use Modules\Payment\Enums\PaymentMethod;

final readonly class InitiatePaymentData
{
    public function __construct(
        public int $orderId,
        public PaymentMethod $method,
        public string $amount,
        public string $currency,
        public ?string $returnUrl = null,
    ) {}
}
