<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Telegram\Actions\GenerateTelegramLinkToken;
use App\Domain\Telegram\Actions\LinkTelegramUser;
use App\Domain\Telegram\Actions\ProcessJoinRequest;
use App\Domain\Telegram\Actions\WatchLesson;
use App\Domain\Telegram\Services\TelegramClient;
use App\Http\Controllers\Controller;
use App\Jobs\SendCourseInviteLinkJob;
use App\Models\Course;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\TelegramCourseInvite;
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
     * Watch a lesson by generating the Telegram message URL.
     * Requires active enrollment and linked Telegram account.
     */
    public function watchLesson(Request $request, CourseItem $item, WatchLesson $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $result = $action->handle($user, $item);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Resend Telegram course invite link to student.
     */
    public function resendInvite(Request $request, string|int $courseParam, TelegramClient $client): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->telegram_user_id) {
            return response()->json([
                'message' => 'يجب ربط حسابك في تليجرام أولاً لاستلام رابط الانضمام.',
            ], 422);
        }

        /** @var Course $course */
        $course = is_numeric($courseParam)
            ? Course::query()->findOrFail((int) $courseParam)
            : Course::query()->where('slug', $courseParam)->firstOrFail();

        // Must have active, non-expired enrollment (staff allowed)
        $isStaff = $user->hasRole(['superadmin', 'admin']) || $user->can('courses.manage');
        if (! $isStaff) {
            $hasActiveEnrollment = Enrollment::query()
                ->where('user_id', $user->getKey())
                ->where('course_id', $course->getKey())
                ->where('status', 'active')
                ->where(function ($q): void {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->exists();

            if (! $hasActiveEnrollment) {
                return response()->json([
                    'message' => 'لا يوجد اشتراك نشط وساري المفعول في هذا المقرر.',
                ], 403);
            }
        }

        if (! $course->telegram_group_id) {
            return response()->json([
                'message' => 'لا توجد مجموعة تليجرام مخصصة لهذا المقرر حالياً.',
            ], 422);
        }

        // Revoke any outstanding invite link for that user + course
        $outstandingInvites = TelegramCourseInvite::query()
            ->where('user_id', $user->getKey())
            ->where('course_id', $course->getKey())
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->get();

        foreach ($outstandingInvites as $invite) {
            if ($invite->invite_link) {
                try {
                    $client->revokeChatInviteLink($course->telegram_group_id, $invite->invite_link);
                } catch (\Throwable $e) {
                    Log::warning('Failed to revoke outstanding chat invite link on resend', [
                        'course_id' => $course->getKey(),
                        'user_id' => $user->getKey(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            $invite->delete();
        }

        SendCourseInviteLinkJob::dispatch($user, $course);

        return response()->json([
            'message' => 'تم إرسال رابط الانضمام الجديد إلى حسابك على تليجرام بنجاح.',
        ]);
    }

    /**
     * Telegram Bot Webhook endpoint.
     * Protected by X-Telegram-Bot-Api-Secret-Token.
     */
    public function webhook(Request $request): JsonResponse
    {
        $secretToken = (string) config('telegram.webhook_secret', '');
        $receivedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        // Fail-closed: Reject all requests if webhook secret is unconfigured or does not match
        if ($secretToken === '' || ! hash_equals($secretToken, $receivedSecret)) {
            Log::warning('Telegram webhook rejected: secret token missing, unconfigured, or invalid');

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $update = $request->all();

        // 1. Handle Chat Join Request
        if (isset($update['chat_join_request'])) {
            try {
                $joinRequest = $update['chat_join_request'];
                $chatId = $joinRequest['chat']['id'] ?? null;
                $fromId = $joinRequest['from']['id'] ?? null;
                $username = $joinRequest['from']['username'] ?? null;

                if ($chatId !== null && $fromId !== null) {
                    app(ProcessJoinRequest::class)->handle($chatId, (int) $fromId, $username);
                }
            } catch (\Throwable $e) {
                Log::error('Error processing telegram chat_join_request update', [
                    'error' => $e->getMessage(),
                    'update' => $update,
                ]);
            }

            return response()->json(['ok' => true]);
        }

        // 2. Handle Message (e.g. /start <token> or general messages)
        if (isset($update['message'])) {
            try {
                $message = $update['message'];
                $text = trim((string) ($message['text'] ?? ''));
                $fromId = $message['from']['id'] ?? null;
                $username = $message['from']['username'] ?? null;
                $chatType = $message['chat']['type'] ?? 'private';

                if ($fromId !== null && $chatType === 'private') {
                    $normalized = mb_strtolower($text);
                    $isStart = str_starts_with($normalized, '/start')
                        || str_starts_with($normalized, 'start')
                        || $normalized === 'ابدأ'
                        || $normalized === '/ابدأ';

                    $parts = preg_split('/\s+/', $text, 2);
                    $token = ($isStart && isset($parts[1]) && trim((string) $parts[1]) !== '') ? trim((string) $parts[1]) : '';

                    if ($token !== '') {
                        app(LinkTelegramUser::class)->handle((int) $fromId, $username, $token);
                    } else {
                        $existingUser = User::query()->where('telegram_user_id', (int) $fromId)->first();
                        if ($existingUser) {
                            $userName = htmlspecialchars((string) $existingUser->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            app(TelegramClient::class)->sendMessage(
                                (int) $fromId,
                                "أهلاً بك يا <b>{$userName}</b>!\nحسابك مرتبط بالفعل بمنصة Codeera بنجاح.\n\nYour account is already linked to Codeera!"
                            );
                        } else {
                            app(TelegramClient::class)->sendMessage(
                                (int) $fromId,
                                "مرحباً بك في بوت منصة كوديرا (Codeera).\nلربط حسابك، يرجى تسجيل الدخول إلى الموقع والضغط على زر \"فتح تليجرام وربط الحساب\" ليتم فتح البوت بالرابط المخصص لحسابك تلقائياً.\n\nWelcome to Codeera Bot. To link your account, please log in to the platform and click \"Open Telegram & Link Account\"."
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Error processing telegram message update', [
                    'error' => $e->getMessage(),
                    'update' => $update,
                ]);
            }

            return response()->json(['ok' => true]);
        }

        // 3. Handle Chat Member Updates (e.g. student joined via invite link)
        if (isset($update['chat_member'])) {
            try {
                $chatMember = $update['chat_member'];
                $newStatus = $chatMember['new_chat_member']['status'] ?? null;
                $userId = $chatMember['new_chat_member']['user']['id'] ?? ($chatMember['from']['id'] ?? null);
                $chatId = $chatMember['chat']['id'] ?? null;

                if ($newStatus === 'member' && $userId !== null && $chatId !== null) {
                    $course = Course::query()
                        ->where(function ($query) use ($chatId): void {
                            $query->where('telegram_group_id', (string) $chatId)
                                ->orWhere('telegram_group_id', (int) $chatId)
                                ->orWhere('telegram_channel_id', (string) $chatId)
                                ->orWhere('telegram_channel_id', (int) $chatId);
                        })
                        ->first();

                    if ($course) {
                        $inviteLink = $chatMember['invite_link']['invite_link'] ?? null;

                        $query = TelegramCourseInvite::query()
                            ->where('course_id', $course->getKey())
                            ->whereNull('used_at');

                        if ($inviteLink) {
                            $query->where('invite_link', $inviteLink);
                        } else {
                            $query->whereHas('user', function ($q) use ($userId): void {
                                $q->where('telegram_user_id', $userId);
                            });
                        }

                        $query->update(['used_at' => now()]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Error processing telegram chat_member update', [
                    'error' => $e->getMessage(),
                    'update' => $update,
                ]);
            }

            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => true]);
    }
}
