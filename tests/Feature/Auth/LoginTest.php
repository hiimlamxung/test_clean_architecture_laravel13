<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_returns_token(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret-pass',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email', 'name']]]);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertStatus(401);
    }

    public function test_login_with_missing_fields_returns_422(): void
    {
        $this->postJson('/api/auth/login', [])->assertStatus(422);
    }
}
