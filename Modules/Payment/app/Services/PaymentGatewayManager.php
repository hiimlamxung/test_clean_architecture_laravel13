<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Payment\Contracts\Payment\PaymentGateway;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Exceptions\PaymentMethodNotSupportedException;

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
