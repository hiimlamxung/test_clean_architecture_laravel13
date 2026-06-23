<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\PaymentRepository;
use App\DTO\Payment\CreatePaymentData;
use App\Enums\PaymentMethod;
use App\Models\Payment;

final readonly class EloquentPaymentRepository implements PaymentRepository
{
    public function create(CreatePaymentData $data): Payment
    {
        return Payment::query()->create([
            'order_id' => $data->orderId,
            'method' => $data->method,
            'status' => $data->status,
            'amount' => $data->amount,
            'currency' => $data->currency,
            'gateway_reference' => $data->gatewayReference,
            'gateway_payload' => $data->gatewayPayload,
        ]);
    }

    public function findByMethodAndReference(PaymentMethod $method, string $reference): ?Payment
    {
        return Payment::query()
            ->where('method', $method)
            ->where('gateway_reference', $reference)
            ->first();
    }

    public function findByMethodAndReferenceOrFail(PaymentMethod $method, string $reference): Payment
    {
        return Payment::query()
            ->where('method', $method)
            ->where('gateway_reference', $reference)
            ->firstOrFail();
    }

    public function save(Payment $payment): Payment
    {
        $payment->save();

        return $payment;
    }
}
