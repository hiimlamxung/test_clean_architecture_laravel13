<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Payment\HandlePaymentWebhookAction;
use App\Actions\Payment\InitiatePaymentAction;
use App\Actions\Payment\VerifyPaymentAction;
use App\Enums\PaymentMethod;
use App\Exceptions\Payment\PaymentMethodNotSupportedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\InitiatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\Order\OrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentController extends Controller
{
    public function __construct(private readonly Gate $gate) {}

    /**
     * @throws AuthorizationException
     */
    public function initiate(
        InitiatePaymentRequest $request,
        InitiatePaymentAction $action,
        OrderService $orders,
    ): PaymentResource {
        $order = $orders->findOrFail($request->orderId());
        $this->gate->authorize('pay', $order);

        $payment = $action->handle($order, $request->method());

        return new PaymentResource($payment);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Payment $payment, VerifyPaymentAction $action): PaymentResource
    {
        $this->gate->authorize('view', $payment);

        $payment = $action->handle($payment);

        return new PaymentResource($payment);
    }

    public function webhook(
        string $method,
        Request $request,
        HandlePaymentWebhookAction $action,
    ): JsonResponse {
        $methodEnum = PaymentMethod::tryFrom($method)
            ?? throw PaymentMethodNotSupportedException::for($method);

        /** @var array<string, string> $headers */
        $headers = array_change_key_case(
            array_map(fn (array $values): string => $values[0] ?? '', $request->headers->all()),
            CASE_LOWER,
        );

        $payment = $action->handle($methodEnum, $request->all(), $headers);

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'status' => $payment->status->value,
            ],
        ]);
    }
}
