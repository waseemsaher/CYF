<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Actions;

use App\Domain\Telegram\Services\TelegramClient;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProcessJoinRequest
{
    public function __construct(
        private readonly TelegramClient $client,
    ) {}

    public function handle(int|string $chatId, int $telegramUserId, ?string $telegramUsername = null): bool
    {
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

        // Check if user is staff (admin / superadmin)
        if ($user->hasRole(['superadmin', 'admin']) || $user->can('courses.manage')) {
            $this->client->approveChatJoinRequest($chatId, $telegramUserId);

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
}
