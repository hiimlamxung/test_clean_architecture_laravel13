<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\OrderRepository;
use App\DTO\Order\CreateOrderData;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

final readonly class EloquentOrderRepository implements OrderRepository
{
    public function create(User $user, CreateOrderData $data): Order
    {
        // Tạo order qua quan hệ của user hiện tại → tự gắn user_id
        return $user->orders()->create([
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
