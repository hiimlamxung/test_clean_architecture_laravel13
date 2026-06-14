<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_redirects_to_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-pass',
        ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password_redirects_back_with_errors(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_with_missing_fields_returns_validation_errors(): void
    {
        $this->from('/login')
            ->post('/login', [])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_login_form_is_accessible_to_guests(): void
    {
        $this->get('/login')->assertOk();
    }
}
