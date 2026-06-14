<?php

declare(strict_types=1);

namespace Modules\Payment\DTO;

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
