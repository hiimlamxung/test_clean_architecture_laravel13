<?php

declare(strict_types=1);

namespace Modules\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Models\User;
use Modules\Order\DTO\CreateOrderData;

final class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'total' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
        ];
    }

    public function toData(): CreateOrderData
    {
        /** @var User $user */
        $user = $this->user();

        return new CreateOrderData(
            userId: $user->id,
            total: $this->float('total'),
            currency: $this->string('currency')->upper()->toString(),
        );
    }
}
