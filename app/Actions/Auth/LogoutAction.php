<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\AuthService;

final readonly class LogoutAction
{
    public function __construct(private AuthService $service) {}

    public function handle(User $user): void
    {
        $this->service->logout($user);
    }
}
