<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Auth\Contracts\Repositories\UserRepository;
use Modules\Auth\Models\User;

final readonly class EloquentUserRepository implements UserRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }
}
