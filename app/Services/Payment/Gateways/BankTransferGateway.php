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
        // Chuyển khoản ngân hàng không có API verify tự động — mặc định Pending,
        // chỉ chuyển trạng thái khi webhook (admin confirm) gọi vào.
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
