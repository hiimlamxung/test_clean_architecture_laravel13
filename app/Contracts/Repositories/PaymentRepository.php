<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\DTO\Payment\CreatePaymentData;
use App\Enums\PaymentMethod;
use App\Models\Payment;

interface PaymentRepository
{
    public function create(CreatePaymentData $data): Payment;

    public function findByMethodAndReference(PaymentMethod $method, string $reference): ?Payment;

    public function findByMethodAndReferenceOrFail(PaymentMethod $method, string $reference): Payment;

    public function save(Payment $payment): Payment;
}
