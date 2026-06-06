<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\PaymentRepository;
use App\Enums\PaymentMethod;
use App\Models\Payment;

final readonly class EloquentPaymentRepository implements PaymentRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Payment
    {
        return Payment::query()->create($attributes);
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
