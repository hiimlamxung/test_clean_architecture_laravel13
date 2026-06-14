<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Enums\PaymentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class InitiatePaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{method: PaymentMethod}>
     */
    public static function methodProvider(): array
    {
        return [
            'momo' => ['method' => PaymentMethod::MomoMethod],
            'paypal' => ['method' => PaymentMethod::PaypalMethod],
            'zalopay' => ['method' => PaymentMethod::ZaloPayMethod],
            'bank_transfer' => ['method' => PaymentMethod::BankTransferMethod],
        ];
    }

    #[DataProvider('methodProvider')]
    public function test_user_can_initiate_payment(PaymentMethod $method): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('payments.initiate', $order), ['method' => $method->value])
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => $method->value,
            'status' => PaymentStatus::Processing->value,
        ]);
    }

    public function test_user_cannot_initiate_payment_for_other_users_order(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->for($owner)->create();

        $this->actingAs(User::factory()->create())
            ->post(route('payments.initiate', $order), ['method' => PaymentMethod::MomoMethod->value])
            ->assertForbidden();
    }
}
