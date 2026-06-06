<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'Pending';
    case Paid = 'Paid';
    case Cancelled = 'Cancelled';
}
