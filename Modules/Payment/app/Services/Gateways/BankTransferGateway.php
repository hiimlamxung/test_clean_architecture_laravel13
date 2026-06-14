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

final readonly class BankTransferGateway implements PaymentGateway
{
    public function __construct(private ConfigRepository $config) {}

    public function initiate(InitiatePaymentData $data): PaymentInitiationResult
    {
        $reference = 'BANK-'.Str::ulid()->toBase32();
        $bankName = (string) $this->config->get('payment.gateways.BankTransfer.bank_name', 'MOCK BANK');
        $accountNo = (string) $this->config->get('payment.gateways.BankTransfer.account_no', '0000000000');
        $accountName = (string) $this->config->get('payment.gateways.BankTransfer.account_name', 'MOCK MERCHANT');

        $qrData = sprintf(
            'BANK:%s|ACC:%s|NAME:%s|AMOUNT:%s|MEMO:%s',
            $bankName, $accountNo, $accountName, $data->amount, $reference,
        );

        return new PaymentInitiationResult(
            gatewayReference: $reference,
            redirectUrl: null,
            qrData: $qrData,
            rawPayload: [
                'bank_name' => $bankName,
                'account_no' => $accountNo,
                'account_name' => $accountName,
                'amount' => $data->amount,
                'memo' => $reference,
            ],
        );
    }

    public function verify(string $gatewayReference): PaymentVerificationResult
    {
        return new PaymentVerificationResult(
            status: PaymentStatus::Pending,
            rawPayload: ['gateway_reference' => $gatewayReference, 'note' => 'Awaiting manual confirmation'],
        );
    }

    public function parseWebhook(array $payload, array $headers): WebhookResult
    {
        $expected = (string) $this->config->get('payment.gateways.BankTransfer.webhook_secret', '');
        $received = $headers['x-mock-signature'] ?? $headers['X-Mock-Signature'] ?? '';

        if ($expected !== '' && ! hash_equals($expected, $received)) {
            throw GatewayException::from('BankTransfer', 'Invalid webhook signature');
        }

        $reference = (string) ($payload['gateway_reference'] ?? '');
        $statusValue = (string) ($payload['status'] ?? '');

        if ($reference === '' || PaymentStatus::tryFrom($statusValue) === null) {
            throw GatewayException::from('BankTransfer', 'Malformed webhook payload', $payload);
        }

        return new WebhookResult(
            gatewayReference: $reference,
            status: PaymentStatus::from($statusValue),
            rawPayload: $payload,
        );
    }
}
