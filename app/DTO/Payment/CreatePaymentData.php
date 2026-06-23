<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

final readonly class CreatePaymentData
{
    /**
     * @param  array<string, mixed>  $gatewayPayload
     */
    public function __construct(
        public int $orderId,
        public PaymentMethod $method,
        public PaymentStatus $status,
        public string $amount,
        public string $currency,
        public ?string $gatewayReference,
        public array $gatewayPayload,
    ) {}
}
