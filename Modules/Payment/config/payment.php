<?php

declare(strict_types=1);

use Modules\Payment\Enums\PaymentStatus;

return [

    /*
    |--------------------------------------------------------------------------
    | Mock default status
    |--------------------------------------------------------------------------
    | Status mà mock gateway trả về khi verify (trừ BankTransfer luôn Pending).
    | Đổi thành "Failed" hoặc "Pending" để test các nhánh khác.
    */
    'mock_default_status' => env('PAYMENT_MOCK_DEFAULT_STATUS', PaymentStatus::Succeeded->value),

    /*
    |--------------------------------------------------------------------------
    | Per-gateway config
    |--------------------------------------------------------------------------
    */
    'gateways' => [

        'Momo' => [
            'partner_code' => env('MOMO_PARTNER_CODE', 'MOCK_MOMO_PARTNER'),
            'access_key' => env('MOMO_ACCESS_KEY', ''),
            'secret_key' => env('MOMO_SECRET_KEY', ''),
            'webhook_secret' => env('MOMO_WEBHOOK_SECRET', 'mock-momo-secret'),
            'return_url' => env('MOMO_RETURN_URL', 'http://localhost:8088/payments/momo/return'),
        ],

        'Paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID', ''),
            'client_secret' => env('PAYPAL_CLIENT_SECRET', ''),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
            'webhook_secret' => env('PAYPAL_WEBHOOK_SECRET', 'mock-paypal-secret'),
            'return_url' => env('PAYPAL_RETURN_URL', 'http://localhost:8088/payments/paypal/return'),
        ],

        'ZaloPay' => [
            'app_id' => env('ZALOPAY_APP_ID', 'MOCK_APP'),
            'key1' => env('ZALOPAY_KEY1', ''),
            'key2' => env('ZALOPAY_KEY2', ''),
            'webhook_secret' => env('ZALOPAY_WEBHOOK_SECRET', 'mock-zalopay-secret'),
            'return_url' => env('ZALOPAY_RETURN_URL', 'http://localhost:8088/payments/zalopay/return'),
        ],

        'BankTransfer' => [
            'bank_name' => env('BANK_NAME', 'Vietcombank'),
            'account_no' => env('BANK_ACCOUNT_NO', '1234567890'),
            'account_name' => env('BANK_ACCOUNT_NAME', 'CONG TY MOCK'),
            'webhook_secret' => env('BANK_WEBHOOK_SECRET', 'mock-bank-secret'),
        ],

    ],

];
