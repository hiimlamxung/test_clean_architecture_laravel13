<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Services\Payment\PaymentService;

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
