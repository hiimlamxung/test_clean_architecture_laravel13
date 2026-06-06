<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Actions\Auth\LoginAction;
use App\DTO\Auth\LoginData;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Hashing\BcryptHasher;
use Tests\TestCase;

final class LoginActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_token_when_credentials_match(): void
    {
        $user = User::factory()->create(['password' => 'right-pass']);

        $result = (new LoginAction(new BcryptHasher))
            ->handle(new LoginData($user->email, 'right-pass'));

        $this->assertNotEmpty($result->token);
        $this->assertSame($user->id, $result->user->id);
    }

    public function test_throws_when_email_not_found(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        (new LoginAction(new BcryptHasher))
            ->handle(new LoginData('nope@example.com', 'whatever'));
    }

    public function test_throws_when_password_wrong(): void
    {
        $user = User::factory()->create(['password' => 'right-pass']);

        $this->expectException(InvalidCredentialsException::class);

        (new LoginAction(new BcryptHasher))
            ->handle(new LoginData($user->email, 'wrong-pass'));
    }
}
