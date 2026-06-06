<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\PaymentMethod;
use App\Services\Payment\Gateways\BankTransferGateway;
use App\Services\Payment\Gateways\MomoGateway;
use App\Services\Payment\Gateways\PaypalGateway;
use App\Services\Payment\Gateways\ZaloPayGateway;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, fn (Application $app): PaymentGatewayManager => new PaymentGatewayManager([
            PaymentMethod::MomoMethod->value => $app->make(MomoGateway::class),
            PaymentMethod::PaypalMethod->value => $app->make(PaypalGateway::class),
            PaymentMethod::ZaloPayMethod->value => $app->make(ZaloPayGateway::class),
            PaymentMethod::BankTransferMethod->value => $app->make(BankTransferGateway::class),
        ]));
    }
}
