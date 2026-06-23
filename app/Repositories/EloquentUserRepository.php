<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\UserRepository;
use App\Models\User;

final readonly class EloquentUserRepository implements UserRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function incrementTotalOrders(int $userId): void
    {
        // Increment atomic ở tầng DB, tránh race condition khi tạo đơn đồng thời
        User::query()->whereKey($userId)->increment('total_orders');
    }
}
