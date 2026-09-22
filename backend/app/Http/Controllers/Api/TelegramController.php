<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Telegram\Actions\GenerateTelegramLinkToken;
use App\Domain\Telegram\Actions\LinkTelegramUser;
use App\Domain\Telegram\Actions\ProcessJoinRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    /**
     * Generate a new one-time link token and deep link for the authenticated user.
     */
    public function generateLinkToken(Request $request, GenerateTelegramLinkToken $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $result = $action->handle($user);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Get the Telegram linking status for the authenticated user.
     */
    public function status(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => [
                'is_linked' => $user->telegram_user_id !== null,
                'telegram_user_id' => $user->telegram_user_id,
                'telegram_username' => $user->telegram_username,
            ],
        ]);
    }

    /**
     * Unlink the user's Telegram account.
     */
    public function unlink(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->update([
            'telegram_user_id' => null,
            'telegram_username' => null,
        ]);

        return response()->json([
            'message' => 'Telegram account unlinked successfully.',
        ]);
    }

    /**
     * Telegram Bot Webhook endpoint.
     * Protected by X-Telegram-Bot-Api-Secret-Token.
     */
    public function webhook(
        Request $request,
        LinkTelegramUser $linkUser,
        ProcessJoinRequest $processJoin,
    ): JsonResponse {
        $secretToken = (string) config('telegram.webhook_secret', '');
        $receivedSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if ($secretToken !== '' && $receivedSecret !== $secretToken) {
            Log::warning('Telegram webhook rejected: invalid secret token header');

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $update = $request->all();

        // 1. Handle Chat Join Request
        if (isset($update['chat_join_request'])) {
            $joinRequest = $update['chat_join_request'];
            $chatId = $joinRequest['chat']['id'] ?? null;
            $fromId = $joinRequest['from']['id'] ?? null;
            $username = $joinRequest['from']['username'] ?? null;

            if ($chatId !== null && $fromId !== null) {
                $processJoin->handle($chatId, (int) $fromId, $username);
            }

            return response()->json(['ok' => true]);
        }

        // 2. Handle Message (e.g. /start <token>)
        if (isset($update['message'])) {
            $message = $update['message'];
            $text = trim((string) ($message['text'] ?? ''));
            $fromId = $message['from']['id'] ?? null;
            $username = $message['from']['username'] ?? null;

            if ($fromId !== null && str_starts_with($text, '/start')) {
                $parts = explode(' ', $text, 2);
                $token = isset($parts[1]) ? trim($parts[1]) : '';

                if ($token !== '') {
                    $linkUser->handle((int) $fromId, $username, $token);
                }
            }

            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => true]);
    }
}
