<?php

declare(strict_types=1);

namespace Modules\Payment\Actions;

use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;

final readonly class HandlePaymentWebhookAction
{
    public function __construct(private PaymentService $service) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function handle(PaymentMethod $method, array $payload, array $headers): Payment
    {
        return $this->service->handleWebhook($method, $payload, $headers);
    }
}
