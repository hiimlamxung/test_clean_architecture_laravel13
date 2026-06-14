<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Contracts\Auth\Factory as AuthManager;

final readonly class LogoutAction
{
    public function __construct(private AuthManager $auth) {}

    public function handle(): void
    {
        $this->auth->guard('web')->logout();
    }
}
