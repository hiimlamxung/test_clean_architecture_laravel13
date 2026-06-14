<?php

declare(strict_types=1);

namespace Modules\Auth\DTO;

use Modules\Auth\Http\Requests\LoginRequest;

final readonly class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            email: $request->string('email')->lower()->toString(),
            password: $request->string('password')->toString(),
            remember: $request->boolean('remember'),
        );
    }
}
