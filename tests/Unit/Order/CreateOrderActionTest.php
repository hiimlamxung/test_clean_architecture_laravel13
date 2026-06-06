<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Actions\Order\CreateOrderAction;
use App\DTO\Order\CreateOrderData;
use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_order_with_pending_status(): void
    {
        $user = User::factory()->create();

        $order = (new CreateOrderAction)->handle(new CreateOrderData(
            userId: $user->id,
            total: 250000.0,
            currency: 'VND',
        ));

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('250000.00', $order->total);
        $this->assertSame('VND', $order->currency);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertDatabaseCount('orders', 1);
    }
}
