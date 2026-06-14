<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\Actions\LogoutAction;
use Modules\Auth\DTO\LoginData;
use Modules\Auth\Exceptions\InvalidCredentialsException;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Core\Http\Controllers\Controller;

final class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth::login');
    }

    public function login(LoginRequest $request, LoginAction $action): RedirectResponse
    {
        try {
            $action->handle(LoginData::fromRequest($request));
        } catch (InvalidCredentialsException $e) {
            return back()
                ->withErrors(['email' => $e->getMessage()])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request, LogoutAction $action): RedirectResponse
    {
        $action->handle();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
