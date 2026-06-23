<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Contracts\Repositories\OrderRepository;
use App\Contracts\Repositories\UserRepository;
use App\DTO\Order\CreateOrderData;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\ConnectionInterface as DatabaseConnection;

final readonly class OrderService
{
    public function __construct(
        private OrderRepository $orders,
        private UserRepository $users,
        private DatabaseConnection $db,
    ) {}

    public function create(User $user, CreateOrderData $data): Order
    {
        // Tạo đơn và tăng counter của user là một khối nguyên tử:
        // nếu bất kỳ bước nào lỗi thì rollback, counter không lệch.
        return $this->db->transaction(function () use ($user, $data): Order {
            $order = $this->orders->create($user, $data);

            $this->users->incrementTotalOrders($user->id);

            return $order;
        });
    }

    public function findOrFail(int $id): Order
    {
        return $this->orders->findOrFail($id);
    }
}
