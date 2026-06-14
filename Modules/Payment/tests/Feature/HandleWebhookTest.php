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

final class HandleWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_marks_payment_paid_when_signature_valid(): void
    {
        config()->set('payment.gateways.Momo.webhook_secret', 'right-secret');

        $order = Order::factory()->for(User::factory())->create();
        $payment = Payment::factory()
            ->for($order)
            ->method(PaymentMethod::MomoMethod)
            ->create([
                'status' => PaymentStatus::Processing,
                'gateway_reference' => 'MOCK-MOMO-ABC',
            ]);

        $this->postJson(
            '/api/payments/webhooks/Momo',
            ['gateway_reference' => 'MOCK-MOMO-ABC', 'status' => 'Succeeded'],
            ['X-Mock-Signature' => 'right-secret'],
        )->assertOk()
            ->assertJsonPath('data.payment_id', $payment->id)
            ->assertJsonPath('data.status', PaymentStatus::Succeeded->value);

        $this->assertSame(OrderStatus::Paid, $order->fresh()?->status);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        config()->set('payment.gateways.Momo.webhook_secret', 'right-secret');

        $this->postJson(
            '/api/payments/webhooks/Momo',
            ['gateway_reference' => 'MOCK-MOMO-X', 'status' => 'Succeeded'],
            ['X-Mock-Signature' => 'wrong'],
        )->assertStatus(502);
    }

    public function test_webhook_rejects_unknown_method(): void
    {
        $this->postJson(
            '/api/payments/webhooks/Bitcoin',
            ['gateway_reference' => 'X', 'status' => 'Succeeded'],
        )->assertStatus(400);
    }
}
