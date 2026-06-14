<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base cho mọi domain exception.
 * Render JSON cho api request, redirect-back kèm error cho web request.
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

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $this->getMessage(),
                'code' => static::class,
                'context' => $this->context,
            ], $this->httpStatus);
        }

        return back()->withErrors(['error' => $this->getMessage()])->withInput();
    }
}
