<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Actions;

use App\Jobs\SendCourseInviteLinkJob;
use App\Jobs\SendTelegramNotificationJob;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GrantEnrollment
{
    public function __construct(
        private readonly ActivateEnrollment $activateEnrollment,
    ) {}

    public function handle(User $student, Course $course, Term $term, User $admin): Enrollment
    {
        return DB::transaction(function () use ($student, $course, $term, $admin): Enrollment {
            $enrollment = $this->activateEnrollment->handle(
                user: $student,
                course: $course,
                term: $term,
                source: 'admin_grant',
                paymentId: null,
                grantedBy: $admin->getKey(),
            );

            activity('enrollment')
                ->performedOn($enrollment)
                ->causedBy($admin)
                ->withProperties([
                    'enrollment_id' => $enrollment->getKey(),
                    'student_id' => $student->getKey(),
                    'course_id' => $course->getKey(),
                    'source' => 'admin_grant',
                ])
                ->log('enrollment_granted');

            // Notify via Telegram if linked (queued, non-blocking)
            if ($student->telegram_user_id) {
                if ($course->getAttribute('telegram_group_id') || $course->getAttribute('telegram_channel_id') || $course->getAttribute('telegram_invite_link')) {
                    SendCourseInviteLinkJob::dispatch($student, $course)->afterCommit();
                } else {
                    $courseTitle = htmlspecialchars((string) ($course->getTranslation('title', 'ar') ?: $course->slug), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $msg = "تم تفعيل اشتراكك في مادة: <b>{$courseTitle}</b> بنجاح!\n\nYour course access is now active!";

                    $generalGroupLink = config('telegram.general_group_link', 'https://t.me/code_eraa');
                    $escapedGeneral = htmlspecialchars((string) $generalGroupLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $msg .= "\n\n🌐 <b>الرابط العام للمنصة:</b>\n{$escapedGeneral}";

                    SendTelegramNotificationJob::dispatch((int) $student->telegram_user_id, $msg)->afterCommit();
                }
            }

            return $enrollment;
        });
    }
}
