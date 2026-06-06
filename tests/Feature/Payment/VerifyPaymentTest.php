<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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

        Sanctum::actingAs($user);

        $this->getJson("/api/payments/{$payment->id}")
            ->assertOk()
            ->assertJsonPath('data.status', PaymentStatus::Succeeded->value)
            ->assertJsonPath('data.paid_at', fn (?string $v): bool => $v !== null);

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

        Sanctum::actingAs($user);

        $this->getJson("/api/payments/{$payment->id}")
            ->assertOk()
            ->assertJsonPath('data.status', PaymentStatus::Failed->value);
    }
}
