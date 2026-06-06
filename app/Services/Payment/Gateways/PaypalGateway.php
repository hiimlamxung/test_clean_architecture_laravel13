<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Contracts\Payment\PaymentGateway;
use App\DTO\Payment\InitiatePaymentData;
use App\DTO\Payment\PaymentInitiationResult;
use App\DTO\Payment\PaymentVerificationResult;
use App\DTO\Payment\WebhookResult;
use App\Enums\PaymentStatus;
use App\Exceptions\Payment\GatewayException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;

final readonly class PaypalGateway implements PaymentGateway
{
    public function __construct(private ConfigRepository $config) {}

    public function initiate(InitiatePaymentData $data): PaymentInitiationResult
    {
        $reference = 'MOCK-PAYPAL-'.Str::ulid()->toBase32();

        return new PaymentInitiationResult(
            gatewayReference: $reference,
            redirectUrl: "https://mock.paypal.local/checkout/{$reference}",
            qrData: null,
            rawPayload: [
                'id' => $reference,
                'intent' => 'CAPTURE',
                'amount' => ['value' => $data->amount, 'currency_code' => $data->currency],
            ],
        );
    }

    public function verify(string $gatewayReference): PaymentVerificationResult
    {
        $status = PaymentStatus::from(
            $this->config->get('payment.mock_default_status', PaymentStatus::Succeeded->value),
        );

        return new PaymentVerificationResult(
            status: $status,
            rawPayload: ['id' => $gatewayReference, 'status' => 'COMPLETED'],
        );
    }

    public function parseWebhook(array $payload, array $headers): WebhookResult
    {
        $expected = (string) $this->config->get('payment.gateways.Paypal.webhook_secret', '');
        $received = $headers['x-mock-signature'] ?? $headers['X-Mock-Signature'] ?? '';

        if ($expected !== '' && ! hash_equals($expected, $received)) {
            throw GatewayException::from('Paypal', 'Invalid webhook signature');
        }

        $reference = (string) ($payload['gateway_reference'] ?? '');
        $statusValue = (string) ($payload['status'] ?? '');

        if ($reference === '' || PaymentStatus::tryFrom($statusValue) === null) {
            throw GatewayException::from('Paypal', 'Malformed webhook payload', $payload);
        }

        return new WebhookResult(
            gatewayReference: $reference,
            status: PaymentStatus::from($statusValue),
            rawPayload: $payload,
        );
    }
}
