<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\Enums\PaymentStatus;

final readonly class PaymentVerificationResult
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public PaymentStatus $status,
        public array $rawPayload,
    ) {}
}
