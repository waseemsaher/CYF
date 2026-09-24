<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Actions;

use App\Domain\Telegram\Services\TelegramClient;
use App\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LinkTelegramUser
{
    public function __construct(
        private readonly TelegramClient $client,
    ) {}

    public function handle(int $telegramUserId, ?string $telegramUsername, string $plainToken): bool
    {
        $tokenHash = hash('sha256', trim($plainToken));

        /** @var TelegramLinkToken|null $linkToken */
        $linkToken = TelegramLinkToken::query()
            ->with('user')
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $linkToken || ! $linkToken->user) {
            $this->client->sendMessage(
                $telegramUserId,
                "❌ الرابط غير صالح أو انتهت صلاحيته. يرجى طلب رابط جديد من حسابك على الموقع.\n\nInvalid or expired link. Please request a new link from the website."
            );

            return false;
        }

        DB::transaction(function () use ($linkToken, $telegramUserId, $telegramUsername): void {
            // Unlink any user previously tied to this telegram_user_id
            User::query()
                ->where('telegram_user_id', $telegramUserId)
                ->where('id', '!=', $linkToken->user_id)
                ->update(['telegram_user_id' => null]);

            $linkToken->user->update([
                'telegram_user_id' => $telegramUserId,
                'telegram_username' => $telegramUsername ?? $linkToken->user->telegram_username,
            ]);

            $linkToken->update([
                'used_at' => now(),
            ]);
        });

        $userName = $linkToken->user->name;
        $this->client->sendMessage(
            $telegramUserId,
            "✅ أهلاً بك يا <b>{$userName}</b>!\nتم ربط حسابك بنجاح في منصة Codeera.\n\nYour Telegram account has been linked successfully!"
        );

        return true;
    }
}
