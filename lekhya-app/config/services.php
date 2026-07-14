<?php
return [
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'ses'      => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'), 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')],
    'resend'   => ['key' => env('RESEND_KEY')],
    'slack'    => ['notifications' => ['bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'), 'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL')]],

    // GST Gateway
    'gst' => [
        'driver'    => env('GST_DRIVER', 'mock'), // mock | masters_india | cleartax | iris
        'client_id' => env('GST_CLIENT_ID'),
        'client_secret' => env('GST_CLIENT_SECRET'),
        'username'  => env('GST_USERNAME'),
        'password'  => env('GST_PASSWORD'),
        'base'      => env('GST_API_BASE'),
        'auth_url'  => env('GST_AUTH_URL'),
        'einvoice_threshold_crore' => env('GST_EINVOICE_THRESHOLD_CRORE', 5),

        // GSTIN verification / lookup — Cashfree Verification Suite.
        // Stays on the offline mock until both Cashfree credentials are set.
        'verify_driver' => env('GST_VERIFY_DRIVER', 'mock'), // mock | cashfree
        'cashfree' => [
            'env'           => env('CASHFREE_ENV', 'production'), // production | sandbox
            'client_id'     => env('CASHFREE_CLIENT_ID'),
            'client_secret' => env('CASHFREE_CLIENT_SECRET'),
        ],
    ],

    // Supabase (for Seedha Bill Mode A connector)
    'supabase' => [
        'url'         => env('SUPABASE_URL'),
        'anon_key'    => env('SUPABASE_ANON_KEY'),
        'service_key' => env('SUPABASE_SERVICE_KEY'),
    ],

    // Seedha Bill REST API (Mode B connector)
    'seedha_bill' => [
        'mode'     => env('SEEDHA_BILL_MODE', 'mock'),  // mock | mode_a | mode_b
        'base_url' => env('SEEDHA_BILL_BASE_URL', 'https://api.seedhabill.com/v1'),
    ],

    // AI / LLM
    'ai' => [
        'driver'      => env('AI_DRIVER', 'lekhya'),
        'endpoint'    => env('AI_ENDPOINT', 'http://localhost:11434/api/generate'),
        'model'       => env('AI_MODEL', 'llama3.2'),
        'max_tokens'  => env('AI_MAX_TOKENS', 4096),
        'temperature' => env('AI_TEMPERATURE', 0.1),
        'use_vision'  => env('AI_USE_VISION', false),
        'anthropic_key' => env('ANTHROPIC_API_KEY'),
        // Primary engine key (env var kept for secret compatibility).
        'primary_key'   => env('AI_PRIMARY_KEY', env('GROQ_API_KEY')),
        // Per-tenant keys stored in ai_settings override these env defaults.
        'text_model'   => env('AI_TEXT_MODEL', env('GROQ_TEXT_MODEL', 'llama-3.3-70b-versatile')),
        'vision_model' => env('AI_VISION_MODEL', env('GROQ_VISION_MODEL', 'meta-llama/llama-4-scout-17b-16e-instruct')),
    ],

    // Razorpay
    'razorpay' => [
        'key_id'         => env('RAZORPAY_KEY_ID'),
        'key_secret'     => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'mode'           => env('RAZORPAY_MODE', 'mock'), // mock | live
        'upi_id'         => env('RAZORPAY_UPI_ID'),      // e.g. business@upi
    ],

    // WhatsApp Business (Meta Cloud API)
    'whatsapp' => [
        'enabled'         => env('WHATSAPP_ENABLED', false),
        'token'           => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_id'     => env('WHATSAPP_BUSINESS_ID'),
    ],

    // Prabhas SSO / central auth
    'prabhas' => [
        'sso_secret'     => env('PRABHAS_SSO_SECRET'),
        'accounts_url'   => env('PRABHAS_ACCOUNTS_URL', 'https://accounts.prabhas.in'),
        'seedhabill_url' => env('PRABHAS_SEEDHABILL_URL', 'https://prabhassaas.in/app/seedhabill/app.html'),
        'logout_url'     => env('PRABHAS_LOGOUT_URL', 'https://accounts.prabhas.in/logout'),
    ],
];
