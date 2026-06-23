<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTO\Auth\LoginData;
use App\DTO\Auth\TokenIssuedData;
use App\Services\Auth\AuthService;

final readonly class LoginAction
{
    public function __construct(private AuthService $service) {}

    public function handle(LoginData $data): TokenIssuedData
    {
        return $this->service->login($data);
    }
}
