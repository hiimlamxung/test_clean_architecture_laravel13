<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'total' => $this->faker->randomFloat(2, 10000, 5000000),
            'currency' => 'VND',
            'status' => OrderStatus::Pending,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => ['status' => OrderStatus::Paid]);
    }
}
