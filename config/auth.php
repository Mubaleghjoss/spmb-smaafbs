<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'pengguna'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'pengguna' => [
            'driver' => 'session',
            'provider' => 'pengguna',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],
        'pengguna' => [
            'driver' => 'eloquent',
            'model' => App\Models\Pengguna::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
        'pengguna' => [
            'provider' => 'pengguna',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    'default_pengguna' => [
        'auto_repair' => env('PENGGUNA_AUTO_REPAIR_DEFAULTS', env('APP_ENV', 'production') !== 'production'),
        'accounts' => [
            [
                'email' => env('SPMB_ADMIN_EMAIL', 'admin@smaalfurqon.sch.id'),
                'password' => env('SPMB_ADMIN_PASSWORD', 'admin123'),
                'nama' => env('SPMB_ADMIN_NAME', 'Administrator'),
                'peran' => 'admin',
            ],
            [
                'email' => env('SPMB_OPERATOR_EMAIL', 'operator@smaalfurqon.sch.id'),
                'password' => env('SPMB_OPERATOR_PASSWORD', 'operator123'),
                'nama' => env('SPMB_OPERATOR_NAME', 'Operator SPMB'),
                'peran' => 'operator',
            ],
        ],
    ],

];
