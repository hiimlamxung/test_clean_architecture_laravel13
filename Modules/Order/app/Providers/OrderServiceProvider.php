<?php

declare(strict_types=1);

namespace Modules\Order\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Order\Contracts\Repositories\OrderRepository;
use Modules\Order\Models\Order;
use Modules\Order\Policies\OrderPolicy;
use Modules\Order\Repositories\EloquentOrderRepository;
use Nwidart\Modules\Support\ModuleServiceProvider;

class OrderServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Order';

    protected string $nameLower = 'order';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Order::class, OrderPolicy::class);
    }
}
