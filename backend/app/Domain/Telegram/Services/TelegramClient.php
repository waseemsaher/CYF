<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramClient
{
    private string $botToken;

    private string $apiUrl;

    public function __construct(?string $botToken = null, ?string $apiUrl = null)
    {
        $this->botToken = $botToken ?? (string) config('telegram.bot_token', '');
        $this->apiUrl = rtrim($apiUrl ?? (string) config('telegram.api_url', 'https://api.telegram.org'), '/');
    }

    public function sendMessage(int|string $chatId, string $text, string $parseMode = 'HTML'): Response
    {
        return $this->post('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ]);
    }

    public function approveChatJoinRequest(int|string $chatId, int $userId): Response
    {
        return $this->post('approveChatJoinRequest', [
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);
    }

    public function declineChatJoinRequest(int|string $chatId, int $userId): Response
    {
        return $this->post('declineChatJoinRequest', [
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Kicks a member by banning and immediately unbanning so they can rejoin upon re-enrollment.
     */
    public function kickChatMember(int|string $chatId, int $userId): bool
    {
        $banResponse = $this->post('banChatMember', [
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);

        if (! $banResponse->successful()) {
            Log::warning('Failed to ban member during kick operation', [
                'chat_id' => $chatId,
                'user_id' => $userId,
                'error' => $banResponse->body(),
            ]);

            return false;
        }

        $unbanResponse = $this->post('unbanChatMember', [
            'chat_id' => $chatId,
            'user_id' => $userId,
            'only_if_banned' => true,
        ]);

        return $unbanResponse->successful();
    }

    /**
     * Get information about a chat (channel, group, etc.) to verify bot access.
     */
    public function getChat(int|string $chatId): Response
    {
        return $this->post('getChat', [
            'chat_id' => $chatId,
        ]);
    }

    /**
     * @param  list<string>  $allowedUpdates
     */
    public function setWebhook(string $url, string $secretToken, array $allowedUpdates = ['message', 'chat_join_request']): Response
    {
        return $this->post('setWebhook', [
            'url' => $url,
            'secret_token' => $secretToken,
            'allowed_updates' => $allowedUpdates,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function post(string $method, array $data): Response
    {
        $url = "{$this->apiUrl}/bot{$this->botToken}/{$method}";

        return Http::timeout(10)->post($url, $data);
    }
}
