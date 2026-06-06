<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use App\Contracts\Payment\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Exceptions\Payment\PaymentMethodNotSupportedException;
use App\Services\Payment\PaymentGatewayManager;
use PHPUnit\Framework\TestCase;

final class PaymentGatewayManagerTest extends TestCase
{
    public function test_it_returns_registered_gateway_for_known_method(): void
    {
        $gateway = $this->createMock(PaymentGateway::class);
        $manager = new PaymentGatewayManager([
            PaymentMethod::MomoMethod->value => $gateway,
        ]);

        $this->assertSame($gateway, $manager->for(PaymentMethod::MomoMethod));
    }

    public function test_it_throws_when_method_is_not_registered(): void
    {
        $manager = new PaymentGatewayManager([]);

        $this->expectException(PaymentMethodNotSupportedException::class);

        $manager->for(PaymentMethod::PaypalMethod);
    }
}
