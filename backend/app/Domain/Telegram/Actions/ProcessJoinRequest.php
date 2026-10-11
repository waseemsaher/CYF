<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Actions;

use App\Domain\Telegram\Services\TelegramClient;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\TelegramCourseInvite;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessJoinRequest
{
    public function __construct(
        private readonly TelegramClient $client,
    ) {}

    public function handle(
        int|string $chatId,
        int $telegramUserId,
        ?string $telegramUsername = null,
        ?string $inviteLink = null,
    ): bool {
        /** @var Course|null $course */
        $course = Course::query()
            ->where(function ($query) use ($chatId): void {
                $query->where('telegram_channel_id', (string) $chatId)
                    ->orWhere('telegram_channel_id', (int) $chatId)
                    ->orWhere('telegram_group_id', (string) $chatId)
                    ->orWhere('telegram_group_id', (int) $chatId);
            })
            ->first();

        if (! $course) {
            Log::info('Telegram join request declined: no course matching chat ID', [
                'chat_id' => $chatId,
                'telegram_user_id' => $telegramUserId,
            ]);

            $this->client->declineChatJoinRequest($chatId, $telegramUserId);

            return false;
        }

        /** @var User|null $user */
        $user = User::query()
            ->where('telegram_user_id', $telegramUserId)
            ->first();

        if (! $user) {
            Log::info('Telegram join request declined: telegram user not linked to any platform user', [
                'chat_id' => $chatId,
                'telegram_user_id' => $telegramUserId,
            ]);

            $this->client->declineChatJoinRequest($chatId, $telegramUserId);

            return false;
        }

        $isStaff = $user->hasRole(['superadmin', 'admin']) || $user->can('courses.manage');

        // Validate one-time invite link if present in the join request
        if ($inviteLink !== null && $inviteLink !== '') {
            /** @var TelegramCourseInvite|null $invite */
            $invite = TelegramCourseInvite::query()
                ->where('course_id', $course->id)
                ->where('invite_link', $inviteLink)
                ->first();

            if ($invite !== null) {
                $isAlreadyUsedOrExpired = $invite->used_at !== null || $invite->expires_at->isPast();
                $isWrongOwner = ! $isStaff && $invite->user_id !== $user->id;

                if ($isAlreadyUsedOrExpired || $isWrongOwner) {
                    Log::info('Telegram join request declined: one-time invite link invalid, already used, expired, or belongs to another student', [
                        'course_id' => $course->id,
                        'user_id' => $user->id,
                        'invite_owner_id' => $invite->user_id,
                        'used_at' => $invite->used_at,
                        'expires_at' => $invite->expires_at,
                    ]);

                    $this->client->declineChatJoinRequest($chatId, $telegramUserId);

                    if ($isAlreadyUsedOrExpired) {
                        $this->safeRevokeInviteLink($chatId, $inviteLink);
                    }

                    return false;
                }
            }
        }

        // Check if user is staff (admin / superadmin)
        if ($isStaff) {
            $this->client->approveChatJoinRequest($chatId, $telegramUserId);
            $this->consumeInviteLink($chatId, $course, $inviteLink);

            activity('telegram')
                ->performedOn($course)
                ->causedBy($user)
                ->log("Approved Telegram join request for staff member (User #{$user->id})");

            return true;
        }

        // Check for an active enrollment for this course
        $hasActiveEnrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($hasActiveEnrollment) {
            $this->client->approveChatJoinRequest($chatId, $telegramUserId);
            $this->consumeInviteLink($chatId, $course, $inviteLink);

            activity('telegram')
                ->performedOn($course)
                ->causedBy($user)
                ->log("Approved Telegram join request for enrolled student (User #{$user->id})");

            return true;
        }

        Log::info('Telegram join request declined: user does not have an active enrollment', [
            'course_id' => $course->id,
            'user_id' => $user->id,
            'telegram_user_id' => $telegramUserId,
        ]);

        $this->client->declineChatJoinRequest($chatId, $telegramUserId);

        activity('telegram')
            ->performedOn($course)
            ->causedBy($user)
            ->log("Declined Telegram join request for User #{$user->id} (no active enrollment)");

        return false;
    }

    /**
     * Marks the invite link as used in the database and revokes it on Telegram so it is strictly one-time use.
     */
    private function consumeInviteLink(int|string $chatId, Course $course, ?string $inviteLink): void
    {
        if ($inviteLink === null || $inviteLink === '') {
            return;
        }

        TelegramCourseInvite::query()
            ->where('course_id', $course->id)
            ->where('invite_link', $inviteLink)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $this->safeRevokeInviteLink($chatId, $inviteLink);
    }

    private function safeRevokeInviteLink(int|string $chatId, string $inviteLink): void
    {
        try {
            $this->client->revokeChatInviteLink($chatId, $inviteLink);
        } catch (Throwable $e) {
            Log::warning('Failed to revoke Telegram invite link after join request', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
