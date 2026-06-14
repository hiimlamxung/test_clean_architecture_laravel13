<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Unit\Gateways;

use Illuminate\Config\Repository as ConfigRepository;
use Modules\Payment\DTO\InitiatePaymentData;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Services\Gateways\BankTransferGateway;
use PHPUnit\Framework\TestCase;

final class BankTransferGatewayTest extends TestCase
{
    private function makeGateway(): BankTransferGateway
    {
        return new BankTransferGateway(new ConfigRepository([
            'payment' => ['gateways' => ['BankTransfer' => [
                'bank_name' => 'TESTBANK',
                'account_no' => '0011223344',
                'account_name' => 'TEST CO',
                'webhook_secret' => '',
            ]]],
        ]));
    }

    public function test_initiate_returns_qr_data_and_no_redirect(): void
    {
        $result = $this->makeGateway()->initiate(new InitiatePaymentData(
            orderId: 11, method: PaymentMethod::BankTransferMethod, amount: '1000000', currency: 'VND',
        ));

        $this->assertStringStartsWith('BANK-', $result->gatewayReference);
        $this->assertNull($result->redirectUrl);
        $this->assertNotNull($result->qrData);
        $this->assertStringContainsString('TESTBANK', $result->qrData);
        $this->assertStringContainsString('0011223344', $result->qrData);
    }

    public function test_verify_always_returns_pending_for_bank_transfer(): void
    {
        $result = $this->makeGateway()->verify('BANK-XYZ');

        $this->assertSame(PaymentStatus::Pending, $result->status);
    }
}
