<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Modules\Core\Exceptions\HttpDomainException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Webhook gateway gọi vào không có CSRF token — exclude khỏi VerifyCsrfToken middleware.
        $middleware->validateCsrfTokens(except: [
            'api/payments/webhooks/*',
        ]);

        // API request không có route 'login' → trả null để Authenticate middleware throw AuthenticationException
        // (sẽ được render thành JSON 401). Web request → redirect đến /login.
        $middleware->redirectGuestsTo(fn (Request $request): ?string => $request->is('api/*') ? null : '/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API luôn trả JSON, kể cả khi client không gửi Accept: application/json.
        $exceptions->shouldRenderJsonWhen(fn (Request $request, \Throwable $e): bool => $request->is('api/*'));

        $exceptions->render(fn (HttpDomainException $e, Request $request) => $e->render($request));
    })->create();
