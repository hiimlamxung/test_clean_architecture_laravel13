<?php

declare(strict_types=1);

namespace App\Exceptions\Payment;

use App\Enums\PaymentMethod;
use Symfony\Component\HttpFoundation\Response;

final class PaymentMethodNotSupportedException extends PaymentException
{
    protected int $httpStatus = Response::HTTP_BAD_REQUEST;

    public static function for(PaymentMethod|string $method): self
    {
        $value = $method instanceof PaymentMethod ? $method->value : $method;

        return (new self("Payment method [{$value}] is not supported."))
            ->withContext(['method' => $value]);
    }
}
