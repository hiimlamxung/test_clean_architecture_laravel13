<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Exceptions\Payment\PaymentMethodNotSupportedException;

final readonly class PaymentGatewayManager
{
    /**
     * @param  array<string, PaymentGateway>  $gateways  map: PaymentMethod->value => gateway instance
     */
    public function __construct(private array $gateways) {}

    public function for(PaymentMethod $method): PaymentGateway
    {
        return $this->gateways[$method->value]
            ?? throw PaymentMethodNotSupportedException::for($method);
    }
}
