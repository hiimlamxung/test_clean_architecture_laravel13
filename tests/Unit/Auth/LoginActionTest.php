<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Actions\Auth\LoginAction;
use App\DTO\Auth\LoginData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_delegates_to_service_and_returns_token(): void
    {
        $user = User::factory()->create(['password' => 'right-pass']);

        // Action uỷ quyền cho AuthService → resolve qua container. Logic chi tiết test ở AuthServiceTest.
        $action = $this->app->make(LoginAction::class);

        $result = $action->handle(new LoginData($user->email, 'right-pass'));

        $this->assertNotEmpty($result->token);
        $this->assertSame($user->id, $result->user->id);
    }
}
