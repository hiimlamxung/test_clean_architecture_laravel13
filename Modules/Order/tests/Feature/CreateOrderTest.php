<?php

declare(strict_types=1);

namespace Modules\Order\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Tests\TestCase;

final class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_order(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('orders.store'), [
                'total' => 250000,
                'currency' => 'vnd',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'currency' => 'VND',
            'status' => 'Pending',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('orders.store'), [
            'total' => 1000,
            'currency' => 'VND',
        ])->assertRedirect('/login');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_validation_rejects_zero_total(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('orders.create'))
            ->post(route('orders.store'), ['total' => 0, 'currency' => 'VND'])
            ->assertRedirect(route('orders.create'))
            ->assertSessionHasErrors('total');
    }
}
