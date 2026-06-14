<?php

declare(strict_types=1);

namespace Modules\Payment\Repositories;

use Modules\Payment\Contracts\Repositories\PaymentRepository;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Models\Payment;

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
