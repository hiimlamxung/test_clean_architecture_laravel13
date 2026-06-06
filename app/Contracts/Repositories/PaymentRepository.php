<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Enums\PaymentMethod;
use App\Models\Payment;

interface PaymentRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Payment;

    public function findByMethodAndReference(PaymentMethod $method, string $reference): ?Payment;

    public function findByMethodAndReferenceOrFail(PaymentMethod $method, string $reference): Payment;

    public function save(Payment $payment): Payment;
}
