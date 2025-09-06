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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 1000),
        'temperature' => env('OPENAI_TEMPERATURE', 0.1),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'api_url' => 'https://api.telegram.org/bot',
        'timeout' => 30,
    ],

    // 'google_drive' => [
    //     'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
    //     'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    //     'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    //     'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
    //     'service_account_json' => env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON'),
    // ],

    'google_drive' => [
        'enabled' => env('GOOGLE_DRIVE_ENABLED', true),

        // Service Account (Recommended - never expires)
        'service_account_json' => env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON'),

        // OAuth Credentials (Fallback)
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),

        // Drive Settings
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
        'supports_all_drives' => env('GOOGLE_DRIVE_SUPPORTS_ALL_DRIVES', true),
    ],

    'google_sheets' => [
        'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),
        'range' => env('GOOGLE_SHEETS_RANGE', 'Sheet1!A1:Z1000'),
    ],

    'aergas' => [
        'photo_max_size' => env('MAX_FILE_SIZE', 20971520), // 20MB
        'allowed_mime_types' => [
            'image/jpeg', 'image/png', 'image/jpg',
            'image/gif', 'image/webp', 'application/pdf'
        ],
        'sla_tracer_hours' => env('AERGAS_SLA_TRACER_HOURS', 24),
        'sla_cgp_hours' => env('AERGAS_SLA_CGP_HOURS', 48),
        'sla_warning_hours' => env('AERGAS_SLA_WARNING_HOURS', 20),
    ],

];
