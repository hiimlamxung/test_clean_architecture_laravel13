<?php

declare(strict_types=1);

namespace Modules\Payment\DTO;

use Modules\Payment\Enums\PaymentStatus;

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
