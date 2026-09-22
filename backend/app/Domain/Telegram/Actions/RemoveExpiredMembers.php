<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Actions;

use App\Domain\Telegram\Services\TelegramClient;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RemoveExpiredMembers
{
    public function __construct(
        private readonly TelegramClient $client,
    ) {}

    /**
     * Finds members whose enrollments have expired or been revoked and kicks them from the Telegram group.
     *
     * @return int Number of removed members
     */
    public function handle(): int
    {
        $coursesWithChats = Course::query()
            ->whereNotNull('telegram_chat_id')
            ->get();

        $removedCount = 0;

        foreach ($coursesWithChats as $course) {
            $chatId = $course->telegram_chat_id;
            if (! $chatId) {
                continue;
            }

            // Find all enrollments for this course that are expired or revoked
            $expiredEnrollments = Enrollment::query()
                ->with('user')
                ->where('course_id', $course->id)
                ->where(function ($query): void {
                    $query->where('status', 'revoked')
                        ->orWhere(function ($q): void {
                            $q->where('status', 'active')
                                ->whereNotNull('expires_at')
                                ->where('expires_at', '<=', now());
                        });
                })
                ->get();

            foreach ($expiredEnrollments as $enrollment) {
                $user = $enrollment->user;
                if (! $user || ! $user->telegram_user_id) {
                    continue;
                }

                // If user is staff/admin, do not remove
                if ($user->hasRole(['superadmin', 'admin']) || $user->can('courses.manage')) {
                    continue;
                }

                // Ensure user doesn't have ANY other valid active enrollment for this course
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
                    continue;
                }

                // Kick the user from the Telegram group (ban + unban)
                $success = $this->client->kickChatMember($chatId, (int) $user->telegram_user_id);

                if ($success) {
                    $removedCount++;
                    Log::info("Removed expired user #{$user->id} from course #{$course->id} Telegram group #{$chatId}");

                    activity('telegram')
                        ->performedOn($course)
                        ->causedBy($user)
                        ->log("Removed expired/revoked member User #{$user->id} from course Telegram chat");
                }
            }
        }

        return $removedCount;
    }
}
