<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts\Repositories;

use Modules\Auth\Models\User;

interface UserRepository
{
    public function findByEmail(string $email): ?User;
}
