<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\DTO\Order\CreateOrderData;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

interface OrderRepository
{
    public function create(User $user, CreateOrderData $data): Order;

    public function findOrFail(int $id): Order;

    public function updateStatus(Order $order, OrderStatus $status): Order;
}
