<?php

declare(strict_types=1);

namespace Modules\Payment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => $this->faker->randomElement(PaymentMethod::cases()),
            'status' => PaymentStatus::Pending,
            'amount' => $this->faker->randomFloat(2, 10000, 5000000),
            'currency' => 'VND',
            'gateway_reference' => 'MOCK-'.Str::ulid()->toBase32(),
            'gateway_payload' => null,
            'paid_at' => null,
        ];
    }

    public function method(PaymentMethod $method): static
    {
        return $this->state(fn (): array => ['method' => $method]);
    }

    public function succeeded(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
        ]);
    }
}
