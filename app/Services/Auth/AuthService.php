<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Contracts\Repositories\UserRepository;
use App\DTO\Auth\LoginData;
use App\DTO\Auth\TokenIssuedData;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class AuthService
{
    public function __construct(
        private UserRepository $users,
        private Hasher $hasher,
    ) {}

    public function login(LoginData $data): TokenIssuedData
    {
        $user = $this->users->findByEmail($data->email);

        if ($user === null || ! $this->hasher->check($data->password, $user->password)) {
            throw InvalidCredentialsException::make();
        }

        $token = $user->createToken('api')->plainTextToken;

        return new TokenIssuedData(token: $token, user: $user);
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
