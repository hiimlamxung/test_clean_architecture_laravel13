<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Controller;
use Modules\Order\Models\Order;
use Modules\Payment\Actions\HandlePaymentWebhookAction;
use Modules\Payment\Actions\InitiatePaymentAction;
use Modules\Payment\Actions\VerifyPaymentAction;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Exceptions\PaymentMethodNotSupportedException;
use Modules\Payment\Models\Payment;

final class PaymentController extends Controller
{
    public function __construct(private readonly Gate $gate) {}

    /**
     * @throws AuthorizationException
     */
    public function initiateForm(Order $order): View
    {
        $this->gate->authorize('pay', $order);

        return view('payment::initiate', [
            'order' => $order,
            'methods' => PaymentMethod::cases(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function initiate(Order $order, Request $request, InitiatePaymentAction $action): RedirectResponse
    {
        $this->gate->authorize('pay', $order);

        $validated = $request->validate([
            'method' => ['required', 'string'],
        ]);

        $method = PaymentMethod::tryFrom($validated['method'])
            ?? throw PaymentMethodNotSupportedException::for($validated['method']);

        $payment = $action->handle($order, $method);

        return redirect()->route('payments.show', $payment);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Payment $payment, VerifyPaymentAction $action): View
    {
        $this->gate->authorize('view', $payment);

        $payment = $action->handle($payment);

        return view('payment::show', ['payment' => $payment]);
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
