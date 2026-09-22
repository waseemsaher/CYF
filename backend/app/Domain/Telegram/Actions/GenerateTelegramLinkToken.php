<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Actions;

use App\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Support\Str;

class GenerateTelegramLinkToken
{
    /**
     * @return array{token: string, deep_link: string, expires_at: string}
     */
    public function handle(User $user): array
    {
        // Expire any existing unused tokens for this user
        TelegramLinkToken::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        $plainToken = Str::random(32);
        $tokenHash = hash('sha256', $plainToken);
        $lifetimeMinutes = (int) config('telegram.token_lifetime_minutes', 15);
        $expiresAt = now()->addMinutes($lifetimeMinutes);

        TelegramLinkToken::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        $botUsername = (string) config('telegram.bot_username', 'AlAzharCoursesBot');
        $deepLink = "https://t.me/{$botUsername}?start={$plainToken}";

        return [
            'token' => $plainToken,
            'deep_link' => $deepLink,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
