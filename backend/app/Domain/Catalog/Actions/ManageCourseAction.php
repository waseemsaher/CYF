<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

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

        $course->fill($attributes);
        $course->save();

        if (is_array($audiences)) {
            $this->syncAudiences($course, $audiences);
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
