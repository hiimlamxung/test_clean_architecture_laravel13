<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Order\CreateOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;

final class OrderController extends Controller
{
    public function __construct(private readonly Gate $gate) {}

    public function store(CreateOrderRequest $request, CreateOrderAction $action): OrderResource
    {
        return new OrderResource($action->handle($request->toData()));
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Order $order): OrderResource
    {
        $this->gate->authorize('view', $order);

        return new OrderResource($order);
    }
}
