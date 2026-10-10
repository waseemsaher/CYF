<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Actions;

use App\Domain\Telegram\Services\TelegramClient;
use App\Models\Course;
use App\Models\TelegramCourseInvite;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generates a single-use Telegram invite link for a course group and delivers it
 * via a private Telegram message to the student.
 *
 * Idempotent: if a valid (unexpired, unspent) invite already exists for the
 * user+course pair, no new API call is made and no duplicate message is sent.
 */
class SendCourseInviteLink
{
    public function __construct(
        private readonly TelegramClient $client,
    ) {}

    /**
     * @return bool true if a message was sent, false if skipped (already outstanding) or no group/channel configured
     */
    public function handle(User $user, Course $course): bool
    {
        $groupId = $course->getAttribute('telegram_group_id');
        $channelId = $course->getAttribute('telegram_channel_id');
        $staticInvite = $course->getAttribute('telegram_invite_link');

        // Nothing to do if the course has no Telegram group, channel, or invite link
        if (! $groupId && ! $channelId && ! $staticInvite) {
            return false;
        }

        // Must have a linked Telegram account
        $telegramUserId = $user->getAttribute('telegram_user_id');
        if (! $telegramUserId) {
            return false;
        }

        // Idempotency: skip if a valid, unused invite was already sent recently
        $existing = TelegramCourseInvite::query()
            ->where('user_id', $user->getKey())
            ->where('course_id', $course->getKey())
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            Log::info('Telegram invite already outstanding; skipping', [
                'user_id' => $user->getKey(),
                'course_id' => $course->getKey(),
                'expires_at' => $existing->getAttribute('expires_at'),
            ]);

            return false;
        }

        // 48-hour expiry window
        $expireDate = now()->addHours(48)->timestamp;

        $channelInviteLink = null;
        if ($channelId) {
            try {
                $this->client->unbanChatMember($channelId, (int) $telegramUserId, true);
            } catch (Throwable $e) {
                Log::warning('Failed to unban student from channel before generating invite link', [
                    'channel_id' => $channelId,
                    'user_id' => $user->getKey(),
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $response = $this->client->createChatInviteLink(
                    chatId: $channelId,
                    memberLimit: 1,
                    expireDate: $expireDate,
                    createsJoinRequest: false,
                );

                if ($response->successful()) {
                    $channelInviteLink = $response->json('result.invite_link');
                } else {
                    Log::error('Failed to create Telegram channel invite link', [
                        'channel_id' => $channelId,
                        'user_id' => $user->getKey(),
                        'response' => $response->body(),
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('Exception creating Telegram channel invite link', [
                    'channel_id' => $channelId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $groupInviteLink = null;
        if ($groupId) {
            try {
                $this->client->unbanChatMember($groupId, (int) $telegramUserId, true);
            } catch (Throwable $e) {
                Log::warning('Failed to unban student from group before generating invite link', [
                    'group_id' => $groupId,
                    'user_id' => $user->getKey(),
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $response = $this->client->createChatInviteLink(
                    chatId: $groupId,
                    memberLimit: 1,
                    expireDate: $expireDate,
                    createsJoinRequest: false,
                );

                if ($response->successful()) {
                    $groupInviteLink = $response->json('result.invite_link');
                } else {
                    Log::error('Failed to create Telegram group invite link', [
                        'group_id' => $groupId,
                        'user_id' => $user->getKey(),
                        'response' => $response->body(),
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('Exception creating Telegram group invite link', [
                    'group_id' => $groupId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $channelInviteLink && ! $groupInviteLink && ! $staticInvite) {
            Log::error('No Telegram invite links could be generated for course', [
                'course_id' => $course->getKey(),
                'user_id' => $user->getKey(),
            ]);

            return false;
        }

        $courseTitle = htmlspecialchars((string) ($course->getTranslation('title', 'ar') ?: $course->slug), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $message = "🎉 مرحباً! تم تفعيل اشتراكك في مادة: <b>{$courseTitle}</b>\n\n";

        if ($channelInviteLink) {
            $escapedChannel = htmlspecialchars((string) $channelInviteLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $message .= "📢 <b>قناة المادة (المحاضرات والملفات والشروحات):</b>\n{$escapedChannel}\n\n";
        }

        if ($groupInviteLink) {
            $escapedGroup = htmlspecialchars((string) $groupInviteLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $message .= "💬 <b>مجموعة النقاش والتواصل:</b>\n{$escapedGroup}\n\n";
        }

        if (! $channelInviteLink && ! $groupInviteLink && $staticInvite) {
            $escapedStatic = htmlspecialchars((string) $staticInvite, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $message .= "🔗 <b>رابط الانضمام إلى المقرر:</b>\n{$escapedStatic}\n\n";
        }

        $message .= '⚠️ الروابط مخصصة لك وصالحة لمدة 48 ساعة فقط. يرجى الانضمام الآن وعدم مشاركتها مع أحد.';

        $sendResponse = $this->client->sendMessage((int) $telegramUserId, $message);

        if (! $sendResponse->successful() || $sendResponse->json('ok') === false) {
            Log::error('Telegram invite message delivery failed', [
                'user_id' => $user->getKey(),
                'telegram_user_id' => $telegramUserId,
                'response' => $sendResponse->body(),
            ]);

            return false;
        }

        // Persist the issued invites only AFTER message was delivered successfully
        if ($channelInviteLink) {
            TelegramCourseInvite::create([
                'user_id' => $user->getKey(),
                'course_id' => $course->getKey(),
                'invite_link' => $channelInviteLink,
                'expires_at' => now()->addHours(48),
            ]);
        }

        if ($groupInviteLink) {
            TelegramCourseInvite::create([
                'user_id' => $user->getKey(),
                'course_id' => $course->getKey(),
                'invite_link' => $groupInviteLink,
                'expires_at' => now()->addHours(48),
            ]);
        }

        return true;
    }
}
