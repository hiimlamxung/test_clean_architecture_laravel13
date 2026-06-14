<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Actions\LogoutAction;
use Modules\Auth\Models\User;
use Tests\TestCase;

final class LogoutActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_out_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertAuthenticatedAs($user);

        $this->app->make(LogoutAction::class)->handle();

        $this->assertGuest();
    }
}
