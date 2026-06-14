<?php

declare(strict_types=1);

namespace Modules\Order\Repositories;

use Modules\Order\Contracts\Repositories\OrderRepository;
use Modules\Order\DTO\CreateOrderData;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;

final readonly class EloquentOrderRepository implements OrderRepository
{
    public function create(CreateOrderData $data): Order
    {
        return Order::query()->create([
            'user_id' => $data->userId,
            'total' => $data->total,
            'currency' => $data->currency,
            'status' => OrderStatus::Pending,
        ]);
    }

    public function findOrFail(int $id): Order
    {
        return Order::query()->findOrFail($id);
    }

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        $order->status = $status;
        $order->save();

        return $order;
    }
}
