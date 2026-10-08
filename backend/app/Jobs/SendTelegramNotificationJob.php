<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Telegram\Exceptions\TelegramApiException;
use App\Domain\Telegram\Services\TelegramClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(
        public int $telegramUserId,
        public string $message,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(TelegramClient $client): void
    {
        try {
            $client->sendMessage($this->telegramUserId, $this->message);
        } catch (TelegramApiException $e) {
            if ($e->isPermanent()) {
                $this->fail($e);

                return;
            }

            throw $e;
        } catch (Throwable $e) {
            Log::error('Failed to send Telegram notification', [
                'telegram_user_id' => $this->telegramUserId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $errorCode = $e instanceof TelegramApiException ? $e->errorCode : null;
        $description = $e instanceof TelegramApiException ? $e->description : $e->getMessage();

        Log::error('SendTelegramNotificationJob permanently failed', [
            'telegram_user_id' => $this->telegramUserId,
            'error_code' => $errorCode,
            'description' => $description,
        ]);
    }
}
