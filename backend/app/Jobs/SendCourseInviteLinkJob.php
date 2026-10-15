<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Telegram\Actions\SendCourseInviteLink;
use App\Domain\Telegram\Exceptions\TelegramApiException;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendCourseInviteLinkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(
        public User $user,
        public Course $course,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(SendCourseInviteLink $action): void
    {
        try {
            $action->handle($this->user, $this->course);
        } catch (TelegramApiException $e) {
            if ($e->isPermanent()) {
                $this->fail($e);

                return;
            }

            throw $e;
        } catch (Throwable $e) {
            Log::error('SendCourseInviteLinkJob failed', [
                'user_id' => $this->user->getKey(),
                'course_id' => $this->course->getKey(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $errorCode = $e instanceof TelegramApiException ? $e->errorCode : null;
        $description = $e instanceof TelegramApiException ? $e->description : $e->getMessage();

        Log::error('SendCourseInviteLinkJob permanently failed', [
            'user_id' => $this->user->getKey(),
            'course_id' => $this->course->getKey(),
            'error_code' => $errorCode,
            'description' => $description,
        ]);
    }
}
