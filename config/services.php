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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'pddikti' => [
        'url' => env('PDDIKTI_API_URL', 'https://pdpt.lldikti4.id/api/rsatuanpendidikan'),
        'timeout' => (int) env('PDDIKTI_TIMEOUT', 15),
    ],

    'recaptcha' => [
        // Kosong/tidak valid = aktif; hanya nilai false eksplisit yang mematikan
        'enabled' => blank($flag = env('RECAPTCHA_ENABLED')) ? true : (filter_var($flag, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true),
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'hostname' => env('RECAPTCHA_HOSTNAME'),
        'min_score' => min(1.0, max(0.0, (float) env('RECAPTCHA_MIN_SCORE', 0.5))),
        'timeout' => (int) env('RECAPTCHA_TIMEOUT', 3),
        'connect_timeout' => (int) env('RECAPTCHA_CONNECT_TIMEOUT', 2),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
