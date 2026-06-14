<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;
use Tests\TestCase;

final class VerifyPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_marks_payment_succeeded_and_order_paid(): void
    {
        config()->set('payment.mock_default_status', PaymentStatus::Succeeded->value);

        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();
        $payment = Payment::factory()
            ->for($order)
            ->method(PaymentMethod::MomoMethod)
            ->create(['status' => PaymentStatus::Processing]);

        $this->actingAs($user)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee(PaymentStatus::Succeeded->value);

        $this->assertSame(OrderStatus::Paid, $order->fresh()?->status);
    }

    public function test_verify_does_not_overwrite_final_status(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();
        $payment = Payment::factory()
            ->for($order)
            ->method(PaymentMethod::MomoMethod)
            ->create(['status' => PaymentStatus::Failed]);

        $this->actingAs($user)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee(PaymentStatus::Failed->value);
    }
}
