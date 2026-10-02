<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Telegram\Services\TelegramClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSetWebhookCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'telegram:set-webhook
                            {--url= : Optional custom webhook URL. Defaults to APP_URL/api/v1/telegram/webhook}
                            {--info : Only display the current webhook info without modifying it}';

    /**
     * @var string
     */
    protected $description = 'Register or inspect the Telegram Bot webhook with Telegram servers';

    public function handle(TelegramClient $client): int
    {
        $botToken = (string) config('telegram.bot_token', '');
        if ($botToken === '') {
            $this->error('TELEGRAM_BOT_TOKEN is not configured.');

            return self::FAILURE;
        }

        if ($this->option('info')) {
            $response = Http::get("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
            $this->info('Current Telegram Webhook Info:');
            $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $secretToken = (string) config('telegram.webhook_secret', '');
        if ($secretToken === '') {
            $this->error('TELEGRAM_WEBHOOK_SECRET is not configured.');

            return self::FAILURE;
        }

        $appUrl = rtrim((string) config('app.url', 'https://api.codeera.tech'), '/');
        $webhookUrl = (string) ($this->option('url') ?: "{$appUrl}/api/v1/telegram/webhook");

        $this->info("Registering webhook URL: {$webhookUrl}");

        $response = $client->setWebhook($webhookUrl, $secretToken, ['message', 'chat_join_request']);

        if ($response->successful() && ($response->json('ok') === true)) {
            $this->info('Telegram webhook registered successfully!');
            $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->error('Failed to register Telegram webhook.');
        $this->error($response->body());

        return self::FAILURE;
    }
}
