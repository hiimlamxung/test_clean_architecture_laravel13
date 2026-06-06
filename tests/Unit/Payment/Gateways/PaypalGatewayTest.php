<?php

declare(strict_types=1);

namespace Tests\Unit\Payment\Gateways;

use App\DTO\Payment\InitiatePaymentData;
use App\Enums\PaymentMethod;
use App\Services\Payment\Gateways\PaypalGateway;
use Illuminate\Config\Repository as ConfigRepository;
use PHPUnit\Framework\TestCase;

final class PaypalGatewayTest extends TestCase
{
    public function test_initiate_returns_paypal_redirect_url_and_no_qr(): void
    {
        $gateway = new PaypalGateway(new ConfigRepository(['payment' => ['gateways' => ['Paypal' => []]]]));

        $result = $gateway->initiate(new InitiatePaymentData(
            orderId: 7, method: PaymentMethod::PaypalMethod, amount: '50.00', currency: 'USD',
        ));

        $this->assertStringStartsWith('MOCK-PAYPAL-', $result->gatewayReference);
        $this->assertNotNull($result->redirectUrl);
        $this->assertStringContainsString('mock.paypal.local', $result->redirectUrl);
        $this->assertNull($result->qrData);
    }
}
