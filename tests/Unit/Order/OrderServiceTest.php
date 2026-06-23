<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Contracts\Repositories\OrderRepository;
use App\Contracts\Repositories\UserRepository;
use App\DTO\Order\CreateOrderData;
use App\Enums\OrderStatus;
use App\Models\User;
use App\Services\Order\OrderService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_order_and_increments_user_counter(): void
    {
        $user = User::factory()->create();

        $order = $this->app->make(OrderService::class)->create($user, new CreateOrderData(
            total: 250000.0,
            currency: 'VND',
        ));

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(1, $user->fresh()->total_orders);
    }

    public function test_counter_accumulates_across_orders(): void
    {
        $user = User::factory()->create();
        $service = $this->app->make(OrderService::class);

        $service->create($user, new CreateOrderData(100000.0, 'VND'));
        $service->create($user, new CreateOrderData(200000.0, 'VND'));

        $this->assertDatabaseCount('orders', 2);
        $this->assertSame(2, $user->fresh()->total_orders);
    }

    public function test_rolls_back_order_when_counter_update_fails(): void
    {
        $user = User::factory()->create();

        // UserRepository giả ném lỗi ở bước tăng counter → transaction phải rollback order vừa tạo.
        $failingUsers = new class implements UserRepository
        {
            public function findByEmail(string $email): ?User
            {
                return null;
            }

            public function incrementTotalOrders(int $userId): void
            {
                throw new RuntimeException('counter update failed');
            }
        };

        $service = new OrderService(
            $this->app->make(OrderRepository::class),
            $failingUsers,
            $this->app->make(ConnectionInterface::class),
        );

        try {
            $service->create($user, new CreateOrderData(250000.0, 'VND'));
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException) {
            // mong đợi
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, $user->fresh()->total_orders);
    }
}
