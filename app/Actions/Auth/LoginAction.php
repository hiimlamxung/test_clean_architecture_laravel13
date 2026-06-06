<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\Repositories\UserRepository;
use App\DTO\Auth\LoginData;
use App\DTO\Auth\TokenIssuedData;
use App\Exceptions\Auth\InvalidCredentialsException;
use Illuminate\Contracts\Hashing\Hasher;

final readonly class LoginAction
{
    public function __construct(
        private UserRepository $users,
        private Hasher $hasher,
    ) {}

    public function handle(LoginData $data): TokenIssuedData
    {
        $user = $this->users->findByEmail($data->email);

        if ($user === null || ! $this->hasher->check($data->password, $user->password)) {
            throw InvalidCredentialsException::make();
        }

        $token = $user->createToken('api')->plainTextToken;

        return new TokenIssuedData(token: $token, user: $user);
    }
}
