<?php

declare(strict_types=1);

namespace Modules\Order\DTO;

final readonly class CreateOrderData
{
    public function __construct(
        public int $userId,
        public float $total,
        public string $currency,
    ) {}
}
