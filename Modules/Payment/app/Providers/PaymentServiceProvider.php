<?php

declare(strict_types=1);

namespace Modules\Payment\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Modules\Payment\Contracts\Repositories\PaymentRepository;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Models\Payment;
use Modules\Payment\Policies\PaymentPolicy;
use Modules\Payment\Repositories\EloquentPaymentRepository;
use Modules\Payment\Services\Gateways\BankTransferGateway;
use Modules\Payment\Services\Gateways\MomoGateway;
use Modules\Payment\Services\Gateways\PaypalGateway;
use Modules\Payment\Services\Gateways\ZaloPayGateway;
use Modules\Payment\Services\PaymentGatewayManager;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PaymentServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Payment';

    protected string $nameLower = 'payment';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(PaymentRepository::class, EloquentPaymentRepository::class);

        $this->app->singleton(PaymentGatewayManager::class, fn (Application $app): PaymentGatewayManager => new PaymentGatewayManager([
            PaymentMethod::MomoMethod->value => $app->make(MomoGateway::class),
            PaymentMethod::PaypalMethod->value => $app->make(PaypalGateway::class),
            PaymentMethod::ZaloPayMethod->value => $app->make(ZaloPayGateway::class),
            PaymentMethod::BankTransferMethod->value => $app->make(BankTransferGateway::class),
        ]));
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Payment::class, PaymentPolicy::class);
    }
}
