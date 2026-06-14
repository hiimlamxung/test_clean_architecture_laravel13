<?php

declare(strict_types=1);

namespace Modules\Order\Actions;

use Modules\Order\Contracts\Repositories\OrderRepository;
use Modules\Order\DTO\CreateOrderData;
use Modules\Order\Models\Order;

final readonly class CreateOrderAction
{
    public function __construct(private OrderRepository $orders) {}

    public function handle(CreateOrderData $data): Order
    {
        return $this->orders->create($data);
    }
}
