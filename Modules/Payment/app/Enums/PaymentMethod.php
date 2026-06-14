<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentMethod: string
{
    case MomoMethod = 'Momo';
    case PaypalMethod = 'Paypal';
    case ZaloPayMethod = 'ZaloPay';
    case BankTransferMethod = 'BankTransfer';

    public function label(): string
    {
        return match ($this) {
            self::MomoMethod => 'MoMo',
            self::PaypalMethod => 'PayPal',
            self::ZaloPayMethod => 'ZaloPay',
            self::BankTransferMethod => 'Chuyển khoản ngân hàng',
        };
    }
}
