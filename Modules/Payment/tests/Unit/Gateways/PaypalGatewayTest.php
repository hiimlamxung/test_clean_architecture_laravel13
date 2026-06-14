<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Unit\Gateways;

use Illuminate\Config\Repository as ConfigRepository;
use Modules\Payment\DTO\InitiatePaymentData;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Services\Gateways\PaypalGateway;
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
