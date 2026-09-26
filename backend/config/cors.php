<?php

declare(strict_types=1);

$allowedOrigins = array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))));

if (env('FRONTEND_URL')) {
    $allowedOrigins[] = rtrim((string) env('FRONTEND_URL'), '/');
}

// In local or testing environments, include local Vite dev and preview URLs
if (env('APP_ENV', 'production') !== 'production') {
    $localDefaults = [
        'http://localhost:5173',
        'http://localhost:4173',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:4173',
    ];
    $allowedOrigins = array_merge($allowedOrigins, $localDefaults);
}

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Strictly limited to explicitly approved origins. Wildcard patterns
    | are completely disallowed to prevent cross-origin credentials leakage.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_filter($allowedOrigins))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,

];
