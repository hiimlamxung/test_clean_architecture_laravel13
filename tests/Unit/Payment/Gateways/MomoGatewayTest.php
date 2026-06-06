<?php

declare(strict_types=1);

namespace Tests\Unit\Payment\Gateways;

use App\DTO\Payment\InitiatePaymentData;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\Payment\GatewayException;
use App\Services\Payment\Gateways\MomoGateway;
use Illuminate\Config\Repository as ConfigRepository;
use PHPUnit\Framework\TestCase;

final class MomoGatewayTest extends TestCase
{
    private function makeGateway(string $defaultStatus = 'Succeeded', string $secret = 'secret'): MomoGateway
    {
        return new MomoGateway(new ConfigRepository([
            'payment' => [
                'mock_default_status' => $defaultStatus,
                'gateways' => ['Momo' => ['webhook_secret' => $secret]],
            ],
        ]));
    }

    public function test_initiate_returns_momo_specific_redirect_url(): void
    {
        $result = $this->makeGateway()->initiate(new InitiatePaymentData(
            orderId: 1, method: PaymentMethod::MomoMethod, amount: '100.00', currency: 'VND',
        ));

        $this->assertStringStartsWith('MOCK-MOMO-', $result->gatewayReference);
        $this->assertNotNull($result->redirectUrl);
        $this->assertStringContainsString('mock.momo.local', $result->redirectUrl);
        $this->assertNull($result->qrData);
    }

    public function test_verify_returns_configured_status(): void
    {
        $result = $this->makeGateway('Failed')->verify('MOCK-MOMO-XYZ');

        $this->assertSame(PaymentStatus::Failed, $result->status);
    }

    public function test_parse_webhook_rejects_invalid_signature(): void
    {
        $this->expectException(GatewayException::class);

        $this->makeGateway(secret: 'right-secret')->parseWebhook(
            payload: ['gateway_reference' => 'MOCK-MOMO-X', 'status' => 'Succeeded'],
            headers: ['x-mock-signature' => 'wrong-secret'],
        );
    }

    public function test_parse_webhook_returns_result_when_signature_matches(): void
    {
        $result = $this->makeGateway(secret: 'right-secret')->parseWebhook(
            payload: ['gateway_reference' => 'MOCK-MOMO-X', 'status' => 'Succeeded'],
            headers: ['x-mock-signature' => 'right-secret'],
        );

        $this->assertSame('MOCK-MOMO-X', $result->gatewayReference);
        $this->assertSame(PaymentStatus::Succeeded, $result->status);
    }

    public function test_parse_webhook_rejects_malformed_payload(): void
    {
        $this->expectException(GatewayException::class);

        $this->makeGateway(secret: '')->parseWebhook(
            payload: ['status' => 'Succeeded'], // thiếu gateway_reference
            headers: [],
        );
    }
}
