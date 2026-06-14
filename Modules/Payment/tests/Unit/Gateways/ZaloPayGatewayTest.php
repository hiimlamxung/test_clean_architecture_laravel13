<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Unit\Gateways;

use Illuminate\Config\Repository as ConfigRepository;
use Modules\Payment\DTO\InitiatePaymentData;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Services\Gateways\ZaloPayGateway;
use PHPUnit\Framework\TestCase;

final class ZaloPayGatewayTest extends TestCase
{
    public function test_initiate_returns_zalopay_redirect_url(): void
    {
        $gateway = new ZaloPayGateway(new ConfigRepository(['payment' => ['gateways' => ['ZaloPay' => ['app_id' => 'APP123']]]]));

        $result = $gateway->initiate(new InitiatePaymentData(
            orderId: 9, method: PaymentMethod::ZaloPayMethod, amount: '200000', currency: 'VND',
        ));

        $this->assertStringStartsWith('MOCK-ZALO-', $result->gatewayReference);
        $this->assertNotNull($result->redirectUrl);
        $this->assertStringContainsString('mock.zalopay.local', $result->redirectUrl);
        $this->assertSame('APP123', $result->rawPayload['app_id']);
    }
}
