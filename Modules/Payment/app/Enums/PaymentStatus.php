<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentStatus: string
{
    case Pending = 'Pending';
    case Processing = 'Processing';
    case Succeeded = 'Succeeded';
    case Failed = 'Failed';
    case Cancelled = 'Cancelled';

    public function isFinal(): bool
    {
        return match ($this) {
            self::Succeeded, self::Failed, self::Cancelled => true,
            self::Pending, self::Processing => false,
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::Succeeded;
    }
}
