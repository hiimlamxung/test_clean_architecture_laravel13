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

final readonly class ZaloPayGateway implements PaymentGateway
{
    public function __construct(private ConfigRepository $config) {}

    public function initiate(InitiatePaymentData $data): PaymentInitiationResult
    {
        $reference = 'MOCK-ZALO-'.Str::ulid()->toBase32();

        return new PaymentInitiationResult(
            gatewayReference: $reference,
            redirectUrl: "https://mock.zalopay.local/order/{$reference}",
            qrData: null,
            rawPayload: [
                'app_id' => $this->config->get('payment.gateways.ZaloPay.app_id', 'MOCK_APP'),
                'app_trans_id' => $reference,
                'amount' => $data->amount,
                'order_url' => "https://mock.zalopay.local/order/{$reference}",
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
            rawPayload: ['app_trans_id' => $gatewayReference, 'return_code' => 1],
        );
    }

    public function parseWebhook(array $payload, array $headers): WebhookResult
    {
        $expected = (string) $this->config->get('payment.gateways.ZaloPay.webhook_secret', '');
        $received = $headers['x-mock-signature'] ?? $headers['X-Mock-Signature'] ?? '';

        if ($expected !== '' && ! hash_equals($expected, $received)) {
            throw GatewayException::from('ZaloPay', 'Invalid webhook signature');
        }

        $reference = (string) ($payload['gateway_reference'] ?? '');
        $statusValue = (string) ($payload['status'] ?? '');

        if ($reference === '' || PaymentStatus::tryFrom($statusValue) === null) {
            throw GatewayException::from('ZaloPay', 'Malformed webhook payload', $payload);
        }

        return new WebhookResult(
            gatewayReference: $reference,
            status: PaymentStatus::from($statusValue),
            rawPayload: $payload,
        );
    }
}
