<?php

return [
    'connection' => [
        'host' => getenv('MT5_HOST') ?: 'localhost',
        'port' => getenv('MT5_PORT') ?: 5000,
        'timeout' => 10,
        'protocol' => 'tcp'
    ],
    'auth' => [
        'login' => getenv('MT5_LOGIN') ?: '',
        'password' => getenv('MT5_PASSWORD') ?: '',
        'server' => getenv('MT5_SERVER') ?: ''
    ],
    'trading' => [
        'enabled' => true,
        'symbols' => [
            'PALMOIL' => [
                'name' => 'Palm Oil',
                'pip' => 0.01,
                'min_lot' => 0.1,
                'max_lot' => 1000,
                'lot_step' => 0.1
            ],
            'CRUDE_OIL' => [
                'name' => 'Crude Oil',
                'pip' => 0.01,
                'min_lot' => 0.1,
                'max_lot' => 1000,
                'lot_step' => 0.1
            ],
            'GOLD' => [
                'name' => 'Gold',
                'pip' => 0.01,
                'min_lot' => 0.01,
                'max_lot' => 100,
                'lot_step' => 0.01
            ],
            'EUR_USD' => [
                'name' => 'Euro vs US Dollar',
                'pip' => 0.0001,
                'min_lot' => 0.01,
                'max_lot' => 500,
                'lot_step' => 0.01
            ]
        ]
    ],
    'risk' => [
        'max_daily_loss_percent' => 2,
        'max_drawdown_percent' => 10,
        'max_concurrent_positions' => 10
    ]
];
