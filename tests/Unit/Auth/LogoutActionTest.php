<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Actions\Auth\LogoutAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LogoutActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_current_personal_access_token(): void
    {
        $user = User::factory()->create();
        $newToken = $user->createToken('api');
        $user->withAccessToken($newToken->accessToken);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        // Action uỷ quyền cho AuthService → resolve qua container.
        $action = $this->app->make(LogoutAction::class);
        $action->handle($user);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
