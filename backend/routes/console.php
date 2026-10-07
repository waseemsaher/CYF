<?php

declare(strict_types=1);

use App\Domain\Telegram\Services\TelegramClient;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('telegram:set-webhook {--info}', function () {
    $botToken = (string) config('telegram.bot_token', '');
    if ($botToken === '') {
        $this->error('TELEGRAM_BOT_TOKEN is not configured.');

        return 1;
    }

    if ($this->option('info')) {
        $response = Http::get("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
        $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return 0;
    }

    $secret = (string) config('telegram.webhook_secret', '');
    if ($secret === '') {
        $this->error('TELEGRAM_WEBHOOK_SECRET is not configured.');

        return 1;
    }

    $url = rtrim((string) config('app.url', 'https://api.codeera.tech'), '/').'/api/v1/telegram/webhook';
    $this->info("Setting webhook to {$url}...");

    $client = app(TelegramClient::class);
    $res = $client->setWebhook($url, $secret);

    $this->line(json_encode($res->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $res->successful() ? 0 : 1;
})->purpose('Register or inspect the Telegram webhook');

Schedule::command('telegram:remove-expired')->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('db:backup')->dailyAt('02:00');
