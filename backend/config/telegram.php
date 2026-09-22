<?php

declare(strict_types=1);

return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
    'bot_username' => env('TELEGRAM_BOT_USERNAME', 'AlAzharCoursesBot'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),
    'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),
    'token_lifetime_minutes' => (int) env('TELEGRAM_TOKEN_LIFETIME_MINUTES', 15),
];
