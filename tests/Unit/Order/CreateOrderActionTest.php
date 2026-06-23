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

    public function test_delegates_to_service_and_creates_order(): void
    {
        $user = User::factory()->create();

        // Action giờ chỉ uỷ quyền cho OrderService → resolve qua container để có DI thật.
        $action = $this->app->make(CreateOrderAction::class);

        $order = $action->handle($user, new CreateOrderData(
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
