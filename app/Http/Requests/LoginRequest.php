<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\Auth\LoginData;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function toData(): LoginData
    {
        return new LoginData(
            email: $this->string('email')->lower()->toString(),
            password: $this->string('password')->toString(),
        );
    }
}
