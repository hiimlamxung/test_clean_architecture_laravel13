<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Contracts\Repositories\UserRepository;
use App\DTO\Auth\LoginData;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Hashing\BcryptHasher;
use Tests\TestCase;

final class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): AuthService
    {
        return new AuthService($this->app->make(UserRepository::class), new BcryptHasher);
    }

    public function test_returns_token_when_credentials_match(): void
    {
        $user = User::factory()->create(['password' => 'right-pass']);

        $result = $this->service()->login(new LoginData($user->email, 'right-pass'));

        $this->assertNotEmpty($result->token);
        $this->assertSame($user->id, $result->user->id);
    }

    public function test_throws_when_email_not_found(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $this->service()->login(new LoginData('nope@example.com', 'whatever'));
    }

    public function test_throws_when_password_wrong(): void
    {
        $user = User::factory()->create(['password' => 'right-pass']);

        $this->expectException(InvalidCredentialsException::class);

        $this->service()->login(new LoginData($user->email, 'wrong-pass'));
    }

    public function test_logout_deletes_current_token(): void
    {
        $user = User::factory()->create();
        $newToken = $user->createToken('api');
        $user->withAccessToken($newToken->accessToken);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->service()->logout($user);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
