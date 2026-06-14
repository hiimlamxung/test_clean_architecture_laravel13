<?php

declare(strict_types=1);

namespace Modules\Payment\Policies;

use Modules\Auth\Models\User;
use Modules\Payment\Models\Payment;

final class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->order->user_id;
    }
}
