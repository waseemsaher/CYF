<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Actions;

use App\Domain\Telegram\Services\TelegramUrlService;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WatchLesson
{
    public function __construct(
        private readonly TelegramUrlService $urlService,
    ) {}

    /**
     * Authorize the user and generate the Telegram message URL for a lesson.
     *
     * @return array{url: string}
     *
     * @throws AuthorizationException
     * @throws HttpException
     */
    public function handle(User $user, CourseItem $item): array
    {
        $course = $item->course;

        if (! $course) {
            abort(404, 'Lesson does not belong to any course.');
        }

        // Staff can always watch
        $isStaff = $user->hasRole(['superadmin', 'admin']) || $user->can('courses.manage');

        if (! $isStaff) {
            // Verify the user has an active enrollment for this course
            $hasActiveEnrollment = Enrollment::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->exists();

            if (! $hasActiveEnrollment) {
                abort(403, 'Active enrollment required to watch this lesson.');
            }
        }

        // Verify Telegram account is connected
        if (! $user->telegram_user_id) {
            abort(422, 'Please connect your Telegram account before watching lessons.');
        }

        // Verify the lesson has a Telegram message mapped
        if (! $item->telegram_message_id) {
            abort(404, 'This lesson does not have a linked Telegram video yet.');
        }

        // Generate the Telegram message URL
        $url = $this->urlService->forLesson($item);

        if (! $url) {
            Log::error('Failed to generate Telegram URL for lesson', [
                'course_item_id' => $item->id,
                'course_id' => $course->id,
            ]);

            abort(500, 'Unable to generate lesson URL. Please try again later.');
        }

        Log::info('Lesson watch URL generated', [
            'user_id' => $user->id,
            'course_item_id' => $item->id,
            'course_id' => $course->id,
        ]);

        return ['url' => $url];
    }
}
