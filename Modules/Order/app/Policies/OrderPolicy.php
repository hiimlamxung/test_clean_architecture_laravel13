<?php

declare(strict_types=1);

namespace Modules\Order\Policies;

use Modules\Auth\Models\User;
use Modules\Order\Models\Order;

final class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    public function pay(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }
}
