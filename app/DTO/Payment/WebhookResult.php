<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\Enums\PaymentStatus;

final readonly class WebhookResult
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public string $gatewayReference,
        public PaymentStatus $status,
        public array $rawPayload,
    ) {}
}
