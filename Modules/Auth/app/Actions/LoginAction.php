<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Contracts\Auth\Factory as AuthManager;
use Illuminate\Contracts\Hashing\Hasher;
use Modules\Auth\Contracts\Repositories\UserRepository;
use Modules\Auth\DTO\LoginData;
use Modules\Auth\Exceptions\InvalidCredentialsException;

final readonly class LoginAction
{
    public function __construct(
        private UserRepository $users,
        private Hasher $hasher,
        private AuthManager $auth,
    ) {}

    public function handle(LoginData $data): void
    {
        $user = $this->users->findByEmail($data->email);

        if ($user === null || ! $this->hasher->check($data->password, $user->password)) {
            throw InvalidCredentialsException::make();
        }

        $this->auth->guard('web')->login($user, $data->remember);
    }
}
