<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\DTO\Order\CreateOrderData;
use App\Enums\OrderStatus;
use App\Models\Order;

interface OrderRepository
{
    public function create(CreateOrderData $data): Order;

    public function findOrFail(int $id): Order;

    public function updateStatus(Order $order, OrderStatus $status): Order;
}
