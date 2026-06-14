<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\DTO\LoginData;
use Modules\Auth\Exceptions\InvalidCredentialsException;
use Modules\Auth\Models\User;
use Tests\TestCase;

final class LoginActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_user_in_when_credentials_match(): void
    {
        $user = User::factory()->create(['password' => 'right-pass']);

        $this->app->make(LoginAction::class)
            ->handle(new LoginData($user->email, 'right-pass'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_throws_when_email_not_found(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $this->app->make(LoginAction::class)
            ->handle(new LoginData('nope@example.com', 'whatever'));
    }

    public function test_throws_when_password_wrong(): void
    {
        $user = User::factory()->create(['password' => 'right-pass']);

        $this->expectException(InvalidCredentialsException::class);

        $this->app->make(LoginAction::class)
            ->handle(new LoginData($user->email, 'wrong-pass'));
    }
}
