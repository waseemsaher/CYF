<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Jobs\SendCourseInviteLinkJob;
use App\Models\Course;
use App\Models\User;

class ManageCourseAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Course
    {
        $audiences = $attributes['audiences'] ?? null;
        unset($attributes['audiences']);

        $course = Course::query()->create($attributes);

        if (is_array($audiences)) {
            $this->syncAudiences($course, $audiences);
        }

        $causer = auth()->user();
        $title = (string) ($course->getTranslation('title', 'ar') ?: $course->getTranslation('title', 'en'));

        $activity = activity('courses')->performedOn($course);
        if ($causer instanceof User) {
            $activity->causedBy($causer);
        }
        $activity->log("Created course {$title}");

        return $course->load('audiences');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Course $course, array $attributes): Course
    {
        $audiences = $attributes['audiences'] ?? null;
        unset($attributes['audiences']);

        $oldChannelId = $course->getAttribute('telegram_channel_id');
        $oldGroupId = $course->getAttribute('telegram_group_id');
        $oldInvite = $course->getAttribute('telegram_invite_link');

        $course->fill($attributes);
        $course->save();

        if (is_array($audiences)) {
            $this->syncAudiences($course, $audiences);
        }

        // If Telegram Channel, Group, or Invite link was newly added or changed, notify active students
        $newChannelId = $course->getAttribute('telegram_channel_id');
        $newGroupId = $course->getAttribute('telegram_group_id');
        $newInvite = $course->getAttribute('telegram_invite_link');

        $hasTelegramUpdated = ($newChannelId && $newChannelId !== $oldChannelId)
            || ($newGroupId && $newGroupId !== $oldGroupId)
            || ($newInvite && $newInvite !== $oldInvite);

        if ($hasTelegramUpdated) {
            $activeStudents = $course->enrollments()
                ->where('status', 'active')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter();

            foreach ($activeStudents as $student) {
                if ($student->telegram_user_id) {
                    SendCourseInviteLinkJob::dispatch($student, $course);
                }
            }
        }

        $causer = auth()->user();
        $title = (string) ($course->getTranslation('title', 'ar') ?: $course->getTranslation('title', 'en'));

        $activity = activity('courses')->performedOn($course);
        if ($causer instanceof User) {
            $activity->causedBy($causer);
        }
        $activity->log("Updated course {$title}");

        return $course->refresh()->load('audiences');
    }

    public function delete(Course $course): void
    {
        $causer = auth()->user();
        $title = (string) ($course->getTranslation('title', 'ar') ?: $course->getTranslation('title', 'en'));

        $activity = activity('courses')->performedOn($course);
        if ($causer instanceof User) {
            $activity->causedBy($causer);
        }
        $activity->log("Deleted course {$title}");

        $course->delete();
    }

    /**
     * @param  array<int, array{academic_year_id: int, department_id: int}>  $audiences
     */
    private function syncAudiences(Course $course, array $audiences): void
    {
        $course->audiences()->delete();
        $course->audiences()->createMany($audiences);
    }
}
