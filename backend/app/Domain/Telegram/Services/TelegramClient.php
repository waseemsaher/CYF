<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Services;

use App\Domain\Telegram\Exceptions\TelegramApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramClient
{
    private string $botToken;

    private string $apiUrl;

    /**
     * Optional custom sleep handler for testing/timing.
     *
     * @var (callable(int): void)|null
     */
    public static $sleepHandler = null;

    public function __construct(?string $botToken = null, ?string $apiUrl = null)
    {
        $this->botToken = $botToken ?? (string) config('telegram.bot_token', '');
        $this->apiUrl = rtrim($apiUrl ?? (string) config('telegram.api_url', 'https://api.telegram.org'), '/');
    }

    public function sendMessage(int|string $chatId, string $text, ?string $parseMode = 'HTML'): Response
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($parseMode !== null) {
            $params['parse_mode'] = $parseMode;
        }

        try {
            return $this->post('sendMessage', $params);
        } catch (TelegramApiException $e) {
            if ($parseMode !== null && $e->isEntityParseError()) {
                Log::warning('Telegram HTML entity parse error; retrying without parse_mode', [
                    'chat_id' => $chatId,
                    'error' => $e->description,
                ]);

                return $this->post('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $text,
                ]);
            }

            throw $e;
        }
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
     * Kicks a member by banning and unbanning so they can rejoin upon re-enrollment.
     */
    public function kickChatMember(int|string $chatId, int $userId): bool
    {
        try {
            $this->post('banChatMember', [
                'chat_id' => $chatId,
                'user_id' => $userId,
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to ban member during kick operation', [
                'chat_id' => $chatId,
                'user_id' => $userId,
                'error' => $this->sanitize($e->getMessage()),
            ]);

            return false;
        }

        // Ban succeeded! Unban immediately with retry and backoff so student isn't permanently stuck banned
        $maxUnbanAttempts = 3;
        for ($unbanAttempt = 1; $unbanAttempt <= $maxUnbanAttempts; $unbanAttempt++) {
            try {
                $this->post('unbanChatMember', [
                    'chat_id' => $chatId,
                    'user_id' => $userId,
                    'only_if_banned' => true,
                ]);

                return true;
            } catch (Throwable $e) {
                Log::warning('Failed to unban member during kick operation, retrying', [
                    'chat_id' => $chatId,
                    'user_id' => $userId,
                    'attempt' => $unbanAttempt,
                    'error' => $this->sanitize($e->getMessage()),
                ]);

                if ($unbanAttempt < $maxUnbanAttempts) {
                    $this->sleep(min($unbanAttempt * 2, 10));
                }
            }
        }

        Log::critical('CRITICAL: Member was banned but unban failed after retries; student remains stuck banned in Telegram', [
            'chat_id' => $chatId,
            'user_id' => $userId,
            'stuck_banned' => true,
        ]);

        return false;
    }

    public function unbanChatMember(int|string $chatId, int $userId, bool $onlyIfBanned = true): Response
    {
        return $this->post('unbanChatMember', [
            'chat_id' => $chatId,
            'user_id' => $userId,
            'only_if_banned' => $onlyIfBanned,
        ]);
    }

    /**
     * Create a single-use invite link for a chat.
     *
     * @param  int  $memberLimit  Max number of members that can join via this link (1 = single-use)
     * @param  int|null  $expireDate  Unix timestamp after which the link is invalid (null = no expiry)
     */
    public function createChatInviteLink(
        int|string $chatId,
        int $memberLimit = 1,
        ?int $expireDate = null,
        bool $createsJoinRequest = false,
    ): Response {
        $params = [
            'chat_id' => $chatId,
            'member_limit' => $memberLimit,
            'creates_join_request' => $createsJoinRequest,
        ];

        if ($expireDate !== null) {
            $params['expire_date'] = $expireDate;
        }

        return $this->post('createChatInviteLink', $params);
    }

    public function revokeChatInviteLink(int|string $chatId, string $inviteLink): Response
    {
        return $this->post('revokeChatInviteLink', [
            'chat_id' => $chatId,
            'invite_link' => $inviteLink,
        ]);
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
    public function setWebhook(string $url, string $secretToken, array $allowedUpdates = ['message', 'chat_join_request', 'chat_member']): Response
    {
        return $this->post('setWebhook', [
            'url' => $url,
            'secret_token' => $secretToken,
            'allowed_updates' => $allowedUpdates,
        ]);
    }

    public function sanitize(string $text): string
    {
        if ($this->botToken !== '') {
            $text = str_replace($this->botToken, '[REDACTED_BOT_TOKEN]', $text);
        }

        return preg_replace('/bot[0-9]+:[a-zA-Z0-9_\-]+/', 'bot[REDACTED_BOT_TOKEN]', $text) ?? $text;
    }

    protected function sleep(int $seconds): void
    {
        if (self::$sleepHandler !== null) {
            (self::$sleepHandler)($seconds);

            return;
        }

        if (app()->environment('testing')) {
            return;
        }

        sleep($seconds);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function post(string $method, array $data): Response
    {
        $url = "{$this->apiUrl}/bot{$this->botToken}/{$method}";
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout(10)->post($url, $data);
            } catch (Throwable $e) {
                $sanitized = $this->sanitize($e->getMessage());
                if ($attempt < $maxAttempts) {
                    $this->sleep(1);

                    continue;
                }

                throw new TelegramApiException(
                    method: $method,
                    errorCode: 0,
                    description: "Network error: {$sanitized}"
                );
            }

            $isOk = $response->successful() && $response->json('ok') !== false;
            if ($isOk) {
                return $response;
            }

            $errorCode = (int) ($response->json('error_code') ?? $response->status());
            $description = (string) ($response->json('description') ?? $response->body());
            $retryAfter = $response->json('parameters.retry_after');
            if ($retryAfter !== null) {
                $retryAfter = (int) $retryAfter;
            }

            // Retry 429 and 5xx inside the client (bounded, honoring retry_after capped at ~10s)
            if (($errorCode === 429 || $errorCode >= 500) && $attempt < $maxAttempts) {
                $delay = $errorCode === 429 && $retryAfter ? min($retryAfter, 10) : min($attempt * 2, 10);
                $this->sleep($delay);

                continue;
            }

            throw new TelegramApiException(
                method: $method,
                errorCode: $errorCode,
                description: $this->sanitize($description),
                retryAfter: $retryAfter
            );
        }

        throw new TelegramApiException(
            method: $method,
            errorCode: 0,
            description: 'Telegram request failed after retries'
        );
    }
}
