<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\TokenResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginAction $action): TokenResource
    {
        return new TokenResource($action->handle($request->toData()));
    }

    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user);

        return response()->json(['message' => 'Logged out']);
    }
}
