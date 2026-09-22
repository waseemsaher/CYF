<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Telegram\Services\TelegramClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $telegramUserId,
        public string $message,
    ) {}

    public function handle(TelegramClient $client): void
    {
        try {
            $client->sendMessage($this->telegramUserId, $this->message);
        } catch (\Throwable $e) {
            Log::error('Failed to send Telegram notification', [
                'telegram_user_id' => $this->telegramUserId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
