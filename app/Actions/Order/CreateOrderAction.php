<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\DTO\Order\CreateOrderData;
use App\Models\Order;
use App\Models\User;
use App\Services\Order\OrderService;

final readonly class CreateOrderAction
{
    public function __construct(private OrderService $service) {}

    public function handle(User $user, CreateOrderData $data): Order
    {
        return $this->service->create($user, $data);
    }
}
