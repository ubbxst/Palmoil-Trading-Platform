<?php

return [
    'public_key' => getenv('PAYSTACK_PUBLIC_KEY') ?: '',
    'secret_key' => getenv('PAYSTACK_SECRET_KEY') ?: '',
    'base_url' => 'https://api.paystack.co',
    'version' => 'v1',
    'timeout' => 30,
    'currency' => 'NGN',
    'payment_channels' => [
        'card',
        'bank',
        'ussd',
        'qr',
        'mobile_money',
        'bank_transfer'
    ],
    'webhook' => [
        'enabled' => true,
        'endpoint' => '/api/payment/webhook.php',
    ],
    'recurring' => [
        'enabled' => true,
        'intervals' => ['monthly', 'quarterly', 'bi-annually', 'annually']
    ],
    'retry' => [
        'enabled' => true,
        'max_attempts' => 3,
        'timeout' => 30
    ],
    'logging' => [
        'enabled' => true,
        'file' => __DIR__ . '/../logs/paystack.log'
    ]
];
