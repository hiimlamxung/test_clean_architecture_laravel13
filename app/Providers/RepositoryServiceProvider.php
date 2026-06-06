<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\OrderRepository;
use App\Contracts\Repositories\PaymentRepository;
use App\Contracts\Repositories\UserRepository;
use App\Repositories\EloquentOrderRepository;
use App\Repositories\EloquentPaymentRepository;
use App\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    private const BINDINGS = [
        UserRepository::class => EloquentUserRepository::class,
        OrderRepository::class => EloquentOrderRepository::class,
        PaymentRepository::class => EloquentPaymentRepository::class,
    ];

    public function register(): void
    {
        foreach (self::BINDINGS as $contract => $concrete) {
            $this->app->bind($contract, $concrete);
        }
    }
}
