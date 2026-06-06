<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_order(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/orders', [
            'total' => 250000,
            'currency' => 'vnd',
        ])->assertCreated()
            ->assertJsonPath('data.currency', 'VND')
            ->assertJsonPath('data.status', 'Pending');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_unauthenticated_user_cannot_create_order(): void
    {
        $this->postJson('/api/orders', ['total' => 1000, 'currency' => 'VND'])
            ->assertUnauthorized();
    }

    public function test_validation_rejects_zero_total(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/orders', ['total' => 0, 'currency' => 'VND'])
            ->assertStatus(422);
    }
}
