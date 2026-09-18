<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'max_tokens' => (int) env('GEMINI_MAX_TOKENS', 8192),
        'timeout' => (int) env('GEMINI_TIMEOUT', 120),
    ],

    'opencode' => [
        'key' => env('OPENCODE_API_KEY'),
        'model' => env('OPENCODE_MODEL', 'deepseek-v4-flash-vision-exp'),
        // Go subscribers use https://opencode.ai/zen/go/v1
        // Zen (pay-as-you-go) uses https://opencode.ai/zen/v1
        'base_url' => env('OPENCODE_BASE_URL', 'https://opencode.ai/zen/go/v1'),
        // Reasoning models spend tokens "thinking" before answering, so the
        // output budget must be generous or the visible answer comes back empty.
        'max_tokens' => (int) env('OPENCODE_MAX_TOKENS', 8192),
        'timeout' => (int) env('OPENCODE_TIMEOUT', 120),
    ],

    'google_health' => [
        'client_id' => env('GOOGLE_HEALTH_CLIENT_ID'),
        'client_secret' => env('GOOGLE_HEALTH_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_HEALTH_REDIRECT', '/settings/google-health/callback'),
    ],

    'openfoodfacts' => [
        'base_url' => env('OPENFOODFACTS_BASE_URL', 'https://world.openfoodfacts.org'),
        'user_agent' => env('OPENFOODFACTS_USER_AGENT', 'openCal/1.0 (self-hosted calorie tracker)'),
    ],

];
