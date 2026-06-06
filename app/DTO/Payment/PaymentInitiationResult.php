<?php

declare(strict_types=1);

namespace App\DTO\Payment;

final readonly class PaymentInitiationResult
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public string $gatewayReference,
        public ?string $redirectUrl,
        public ?string $qrData,
        public array $rawPayload,
    ) {}
}
