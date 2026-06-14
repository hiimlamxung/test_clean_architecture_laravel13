<?php

declare(strict_types=1);

namespace Modules\Payment\Services\Gateways;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;
use Modules\Payment\Contracts\Payment\PaymentGateway;
use Modules\Payment\DTO\InitiatePaymentData;
use Modules\Payment\DTO\PaymentInitiationResult;
use Modules\Payment\DTO\PaymentVerificationResult;
use Modules\Payment\DTO\WebhookResult;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Exceptions\GatewayException;

final readonly class MomoGateway implements PaymentGateway
{
    public function __construct(private ConfigRepository $config) {}

    public function initiate(InitiatePaymentData $data): PaymentInitiationResult
    {
        $reference = 'MOCK-MOMO-'.Str::ulid()->toBase32();

        return new PaymentInitiationResult(
            gatewayReference: $reference,
            redirectUrl: "https://mock.momo.local/pay/{$reference}",
            qrData: null,
            rawPayload: [
                'partnerCode' => $this->config->get('payment.gateways.Momo.partner_code', 'MOCK_PARTNER'),
                'requestId' => $reference,
                'amount' => $data->amount,
                'orderId' => (string) $data->orderId,
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
            rawPayload: ['gateway_reference' => $gatewayReference, 'resultCode' => 0],
        );
    }

    public function parseWebhook(array $payload, array $headers): WebhookResult
    {
        $expected = (string) $this->config->get('payment.gateways.Momo.webhook_secret', '');
        $received = $headers['x-mock-signature'] ?? $headers['X-Mock-Signature'] ?? '';

        if ($expected !== '' && ! hash_equals($expected, $received)) {
            throw GatewayException::from('Momo', 'Invalid webhook signature');
        }

        $reference = (string) ($payload['gateway_reference'] ?? '');
        $statusValue = (string) ($payload['status'] ?? '');

        if ($reference === '' || PaymentStatus::tryFrom($statusValue) === null) {
            throw GatewayException::from('Momo', 'Malformed webhook payload', $payload);
        }

        return new WebhookResult(
            gatewayReference: $reference,
            status: PaymentStatus::from($statusValue),
            rawPayload: $payload,
        );
    }
}
