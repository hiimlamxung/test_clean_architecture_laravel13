<?php

declare(strict_types=1);

namespace Modules\Order\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Controller;
use Modules\Order\Actions\CreateOrderAction;
use Modules\Order\Http\Requests\CreateOrderRequest;
use Modules\Order\Models\Order;

final class OrderController extends Controller
{
    public function __construct(private readonly Gate $gate) {}

    public function index(Request $request): View
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('order::index', ['orders' => $orders]);
    }

    public function create(): View
    {
        return view('order::create');
    }

    public function store(CreateOrderRequest $request, CreateOrderAction $action): RedirectResponse
    {
        $order = $action->handle($request->toData());

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Đã tạo đơn hàng #'.$order->id);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Order $order): View
    {
        $this->gate->authorize('view', $order);

        return view('order::show', ['order' => $order]);
    }
}
