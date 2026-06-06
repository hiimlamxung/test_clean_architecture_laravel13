<?php

declare(strict_types=1);

namespace App\DTO\Auth;

use App\Models\User;

final readonly class TokenIssuedData
{
    public function __construct(
        public string $token,
        public User $user,
    ) {}
}
