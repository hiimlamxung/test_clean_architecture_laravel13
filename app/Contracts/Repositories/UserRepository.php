<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;

interface UserRepository
{
    public function findByEmail(string $email): ?User;
}
