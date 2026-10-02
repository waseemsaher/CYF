<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Telegram\Actions\SendCourseInviteLink;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendCourseInviteLinkJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public Course $course,
    ) {}

    public function handle(SendCourseInviteLink $action): void
    {
        try {
            $action->handle($this->user, $this->course);
        } catch (\Throwable $e) {
            Log::error('SendCourseInviteLinkJob failed', [
                'user_id' => $this->user->getKey(),
                'course_id' => $this->course->getKey(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
