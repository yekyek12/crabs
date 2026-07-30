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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ai' => [
        'url' => env('AI_SERVICE_URL', 'http://127.0.0.1:9000'),
        'token' => env('AI_SERVICE_TOKEN'),
        'timeout' => env('AI_REQUEST_TIMEOUT', 60),
        'health_timeout' => env('AI_HEALTH_TIMEOUT', 12),
        'retry_enabled' => env('AI_RETRY_ENABLED', true),
        'confidence_threshold' => env('AI_CONFIDENCE_THRESHOLD', 0.60),
        'high_confidence_threshold' => env('AI_HIGH_CONFIDENCE_THRESHOLD', 0.85),
        'fast_mode_enabled' => env('AI_FAST_MODE_ENABLED', false),
        'fast_min_confidence' => env('AI_FAST_MIN_CONFIDENCE', env('AI_CONFIDENCE_THRESHOLD', 0.60)),
        'consensus_enabled' => env('AI_CONSENSUS_ENABLED', true),
        'required_provider_count' => env('AI_REQUIRED_PROVIDER_COUNT', 6),
        'min_provider_agreement' => env('AI_MIN_PROVIDER_AGREEMENT', 4),
        'allow_single_provider_result' => env('AI_ALLOW_SINGLE_PROVIDER_RESULT', false),
        'global_detection' => env('AI_GLOBAL_DETECTION', true),
        'fast_provider_order' => array_values(array_filter(array_map('trim', explode(',', env('AI_FAST_PROVIDER_ORDER', 'local,gemini'))))),
        'provider_order' => array_values(array_filter(array_map('trim', explode(',', env('AI_PROVIDER_ORDER', 'gemini,anthropic,groq,openrouter,cohere,wisdomgate'))))),
        'providers' => [
            'gemini' => [
                'key' => env('GEMINI_API_KEY'),
                'model' => env('GEMINI_MODEL', 'gemini-2.5-flash-lite'),
            ],
            'cohere' => [
                'key' => env('COHERE_API_KEY'),
                'model' => env('COHERE_MODEL', 'command-r7b-12-2024'),
            ],
            'groq' => [
                'key' => env('GROQ_API_KEY'),
                'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
            ],
            'openrouter' => [
                'key' => env('OPENROUTER_API_KEY'),
                'model' => env('OPENROUTER_MODEL', 'openrouter/free'),
            ],
            'anthropic' => [
                'key' => env('ANTHROPIC_API_KEY'),
                'model' => env('ANTHROPIC_MODEL', 'claude-haiku-3-5-20241022'),
                'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
            ],
            'wisdomgate' => [
                'key' => env('WISDOMGATE_API_KEY'),
                'model' => env('WISDOMGATE_MODEL', 'gpt-5-nano'),
            ],
        ],
    ],

    'location' => [
        'max_device_accuracy_meters' => env('LOCATION_MAX_DEVICE_ACCURACY_METERS', 100),
    ],

];
