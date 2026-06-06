<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base cho mọi domain exception cần render ra JSON với status code riêng.
 * Subclass override $httpStatus để chỉ định mã HTTP phù hợp.
 */
abstract class HttpDomainException extends RuntimeException
{
    protected int $httpStatus = Response::HTTP_UNPROCESSABLE_ENTITY;

    /** @var array<string, mixed> */
    protected array $context = [];

    /**
     * @param  array<string, mixed>  $context
     */
    public function withContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => static::class,
            'context' => $this->context,
        ], $this->httpStatus);
    }
}
