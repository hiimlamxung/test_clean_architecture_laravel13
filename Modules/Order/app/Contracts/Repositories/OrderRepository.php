<?php

declare(strict_types=1);

namespace Modules\Order\Contracts\Repositories;

use Modules\Order\DTO\CreateOrderData;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;

interface OrderRepository
{
    public function create(CreateOrderData $data): Order;

    public function findOrFail(int $id): Order;

    public function updateStatus(Order $order, OrderStatus $status): Order;
}
