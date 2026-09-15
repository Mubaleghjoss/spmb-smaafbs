<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'akses_sync' => [
        'token' => env('AKSES_SYNC_TOKEN'),
        'require_https' => env('AKSES_SYNC_REQUIRE_HTTPS', true),
    ],

    'spmb_data_bot' => [
        'token' => env('SPMB_DATA_BOT_TOKEN'),
        'webhook_url' => env('SPMB_DATA_BOT_WEBHOOK_URL'),
        'webhook_secret' => env('SPMB_DATA_BOT_WEBHOOK_SECRET'),
        'webhook_timeout' => env('SPMB_DATA_BOT_WEBHOOK_TIMEOUT', 5),
    ],

    'spmb' => [
        'synthetic_secret' => env('SPMB_SYNTHETIC_SECRET'),
    ],

];
