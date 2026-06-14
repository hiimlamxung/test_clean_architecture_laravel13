<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Repositories;

use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Models\Payment;

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
