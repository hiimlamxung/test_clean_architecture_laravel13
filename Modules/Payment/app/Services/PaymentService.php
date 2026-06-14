<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Database\ConnectionInterface as DatabaseConnection;
use Illuminate\Support\Carbon;
use Modules\Order\Contracts\Repositories\OrderRepository;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Modules\Payment\Contracts\Repositories\PaymentRepository;
use Modules\Payment\DTO\InitiatePaymentData;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;

final readonly class PaymentService
{
    public function __construct(
        private PaymentGatewayManager $gatewayManager,
        private PaymentRepository $payments,
        private OrderRepository $orders,
        private DatabaseConnection $db,
    ) {}

    public function initiate(Order $order, PaymentMethod $method): Payment
    {
        $data = new InitiatePaymentData(
            orderId: $order->id,
            method: $method,
            amount: (string) $order->total,
            currency: $order->currency,
        );

        $result = $this->gatewayManager->for($method)->initiate($data);

        return $this->payments->create([
            'order_id' => $order->id,
            'method' => $method,
            'status' => PaymentStatus::Processing,
            'amount' => $order->total,
            'currency' => $order->currency,
            'gateway_reference' => $result->gatewayReference,
            'gateway_payload' => [
                'initiation' => $result->rawPayload,
                'redirect_url' => $result->redirectUrl,
                'qr_data' => $result->qrData,
            ],
        ]);
    }

    public function verify(Payment $payment): Payment
    {
        if ($payment->status->isFinal()) {
            return $payment;
        }

        if ($payment->gateway_reference === null) {
            return $payment;
        }

        $result = $this->gatewayManager
            ->for($payment->method)
            ->verify($payment->gateway_reference);

        return $this->applyStatus($payment, $result->status, $result->rawPayload, 'verify');
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(PaymentMethod $method, array $payload, array $headers): Payment
    {
        $result = $this->gatewayManager->for($method)->parseWebhook($payload, $headers);

        $payment = $this->payments->findByMethodAndReferenceOrFail($method, $result->gatewayReference);

        return $this->applyStatus($payment, $result->status, $result->rawPayload, 'webhook');
    }

    /**
     * @param  array<string, mixed>  $rawPayload
     */
    private function applyStatus(
        Payment $payment,
        PaymentStatus $newStatus,
        array $rawPayload,
        string $source,
    ): Payment {
        return $this->db->transaction(function () use ($payment, $newStatus, $rawPayload, $source): Payment {
            $payment->status = $newStatus;
            $payment->gateway_payload = ($payment->gateway_payload ?? []) + [$source => $rawPayload];

            if ($newStatus === PaymentStatus::Succeeded && $payment->paid_at === null) {
                $payment->paid_at = Carbon::now();
            }

            $this->payments->save($payment);

            if ($newStatus === PaymentStatus::Succeeded) {
                $this->orders->updateStatus($payment->order, OrderStatus::Paid);
            }

            return $payment;
        });
    }
}
