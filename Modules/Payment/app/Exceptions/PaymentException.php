<?php

declare(strict_types=1);

namespace Modules\Payment\Exceptions;

use Modules\Core\Exceptions\HttpDomainException;
use Symfony\Component\HttpFoundation\Response;

class PaymentException extends HttpDomainException
{
    protected int $httpStatus = Response::HTTP_UNPROCESSABLE_ENTITY;
}
