<?php

declare(strict_types=1);

namespace App\DTO\Order;

final readonly class CreateOrderData
{
    public function __construct(
        public int $userId,
        public float $total,
        public string $currency,
    ) {}
}
