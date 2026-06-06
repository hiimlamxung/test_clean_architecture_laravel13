<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class InitiatePaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{method: PaymentMethod, expectsRedirect: bool, expectsQr: bool}>
     */
    public static function methodProvider(): array
    {
        return [
            'momo' => ['method' => PaymentMethod::MomoMethod, 'expectsRedirect' => true, 'expectsQr' => false],
            'paypal' => ['method' => PaymentMethod::PaypalMethod, 'expectsRedirect' => true, 'expectsQr' => false],
            'zalopay' => ['method' => PaymentMethod::ZaloPayMethod, 'expectsRedirect' => true, 'expectsQr' => false],
            'bank_transfer' => ['method' => PaymentMethod::BankTransferMethod, 'expectsRedirect' => false, 'expectsQr' => true],
        ];
    }

    #[DataProvider('methodProvider')]
    public function test_user_can_initiate_payment(PaymentMethod $method, bool $expectsRedirect, bool $expectsQr): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/payments/initiate', [
            'order_id' => $order->id,
            'method' => $method->value,
        ])->assertCreated();

        $response->assertJsonPath('data.method', $method->value);
        $response->assertJsonPath('data.status', PaymentStatus::Processing->value);

        $payload = $response->json('data');
        $this->assertSame($expectsRedirect, $payload['redirect_url'] !== null);
        $this->assertSame($expectsQr, $payload['qr_data'] !== null);

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
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/payments/initiate', [
            'order_id' => $order->id,
            'method' => PaymentMethod::MomoMethod->value,
        ])->assertForbidden();
    }
}
