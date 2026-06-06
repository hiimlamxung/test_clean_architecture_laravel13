<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

use App\Exceptions\HttpDomainException;
use Symfony\Component\HttpFoundation\Response;

final class InvalidCredentialsException extends HttpDomainException
{
    protected int $httpStatus = Response::HTTP_UNAUTHORIZED;

    public static function make(): self
    {
        return new self(__('auth.failed'));
    }
}
