<?php

declare(strict_types=1);

use App\Exceptions\HttpDomainException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Để default cho web/api middleware groups được Laravel khởi tạo (xem framework MiddlewareTrait::use/web/api).
        // KHÔNG xoá closure này: Laravel chỉ apply default middleware khi withMiddleware() được gọi.

        // API request không có route 'login' → trả null để Authenticate middleware throw AuthenticationException
        // (sẽ được render thành JSON 401 bởi shouldRenderJsonWhen ở dưới) thay vì cố redirect đến route 'login'.
        $middleware->redirectGuestsTo(fn (Request $request): ?string => $request->is('api/*') ? null : '/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API luôn trả JSON, kể cả khi client không gửi Accept: application/json.
        $exceptions->shouldRenderJsonWhen(fn (Request $request, \Throwable $e): bool => $request->is('api/*'));

        $exceptions->render(fn (HttpDomainException $e, Request $request) => $e->render($request));
    })->create();
