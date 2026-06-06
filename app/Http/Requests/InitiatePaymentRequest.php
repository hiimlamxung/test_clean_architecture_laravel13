<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Contracts\Repositories\OrderRepository;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|ValidationRule|Enum>>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')],
            'method' => ['required', new Enum(PaymentMethod::class)],
        ];
    }

    public function method(): PaymentMethod
    {
        return PaymentMethod::from($this->string('method')->toString());
    }

    public function order(OrderRepository $orders): Order
    {
        return $orders->findOrFail($this->integer('order_id'));
    }
}
