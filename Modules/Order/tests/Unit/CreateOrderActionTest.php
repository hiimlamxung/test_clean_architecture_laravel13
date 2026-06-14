<?php

declare(strict_types=1);

namespace Modules\Order\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Order\Actions\CreateOrderAction;
use Modules\Order\DTO\CreateOrderData;
use Modules\Order\Enums\OrderStatus;
use Tests\TestCase;

final class CreateOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_order_with_pending_status(): void
    {
        $user = User::factory()->create();

        $order = $this->app->make(CreateOrderAction::class)->handle(new CreateOrderData(
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
