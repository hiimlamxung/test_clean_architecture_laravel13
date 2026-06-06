<?php

declare(strict_types=1);

namespace App\Exceptions\Payment;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class GatewayException extends PaymentException
{
    protected int $httpStatus = Response::HTTP_BAD_GATEWAY;

    /**
     * @param  array<string, mixed>  $context
     */
    public static function from(string $gatewayName, string $reason, array $context = [], ?Throwable $previous = null): self
    {
        return (new self("Gateway [{$gatewayName}] failed: {$reason}", 0, $previous))
            ->withContext(['gateway' => $gatewayName] + $context);
    }
}
