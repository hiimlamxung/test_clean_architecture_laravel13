<?php

declare(strict_types=1);

namespace App\Contracts\Payment;

use App\DTO\Payment\InitiatePaymentData;
use App\DTO\Payment\PaymentInitiationResult;
use App\DTO\Payment\PaymentVerificationResult;
use App\DTO\Payment\WebhookResult;
use App\Exceptions\Payment\GatewayException;

interface PaymentGateway
{
    /**
     * Khởi tạo giao dịch tại gateway, trả về thông tin để client redirect / quét QR.
     *
     * @throws GatewayException
     */
    public function initiate(InitiatePaymentData $data): PaymentInitiationResult;

    /**
     * Hỏi gateway trạng thái hiện tại của 1 giao dịch theo gateway reference.
     *
     * @throws GatewayException
     */
    public function verify(string $gatewayReference): PaymentVerificationResult;

    /**
     * Parse + verify signature webhook payload do gateway gửi về.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     *
     * @throws GatewayException
     */
    public function parseWebhook(array $payload, array $headers): WebhookResult;
}
