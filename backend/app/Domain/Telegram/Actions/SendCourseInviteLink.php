<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Actions;

use App\Domain\Telegram\Services\TelegramClient;
use App\Models\Course;
use App\Models\TelegramCourseInvite;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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
     * @return bool  true if a message was sent, false if skipped (already outstanding) or no group configured
     */
    public function handle(User $user, Course $course): bool
    {
        // Nothing to do if the course has no Telegram group
        $chatId = $course->getAttribute('telegram_group_id');
        if (! $chatId) {
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

        $courseTitle = $course->getTranslation('title', 'ar') ?: $course->slug;
        $staticLink  = $course->getAttribute('telegram_invite_link');

        // Call Telegram to create the single-use invite
        $response = $this->client->createChatInviteLink(
            chatId: $chatId,
            memberLimit: 1,
            expireDate: $expireDate,
            createsJoinRequest: false,
        );

        if (! $response->successful()) {
            Log::error('Failed to create Telegram chat invite link', [
                'chat_id'   => $chatId,
                'user_id'   => $user->getKey(),
                'course_id' => $course->getKey(),
                'response'  => $response->body(),
            ]);

            // Fall back: send plain approval message with the static join-request link if available
            if ($staticLink) {
                $fallback = "🎉 تم تفعيل اشتراكك في مادة: <b>{$courseTitle}</b>!\n\n"
                    . "اضغط على الرابط التالي للانضمام إلى القناة:\n"
                    . "<a href=\"{$staticLink}\">{$staticLink}</a>";
                $this->client->sendMessage((int) $telegramUserId, $fallback);
            }

            return false;
        }

        $inviteLink = $response->json('result.invite_link');
        if (! $inviteLink) {
            Log::error('Telegram createChatInviteLink returned no invite_link', [
                'response' => $response->json(),
            ]);

            return false;
        }

        // Persist the issued invite so we can guard against duplicates
        TelegramCourseInvite::create([
            'user_id'    => $user->getKey(),
            'course_id'  => $course->getKey(),
            'invite_link' => $inviteLink,
            'expires_at' => now()->addHours(48),
        ]);

        // Build the message — always include the static join-request link so the
        // student has a reliable fallback if they can't use the single-use link.
        $message = "🎉 تم قبول دفعك وتفعيل اشتراكك في مادة: <b>{$courseTitle}</b>!\n\n";

        if ($staticLink) {
            $message .= "اضغط على الرابط التالي للانضمام إلى القناة:\n"
                . "<a href=\"{$staticLink}\">{$staticLink}</a>";
        } else {
            $message .= "رابط الانضمام الشخصي (صالح لمرة واحدة · 48 ساعة):\n"
                . "{$inviteLink}\n\n"
                . "⚠️ لا تشارك هذا الرابط مع أحد.";
        }

        $this->client->sendMessage((int) $telegramUserId, $message);

        return true;
    }
}
