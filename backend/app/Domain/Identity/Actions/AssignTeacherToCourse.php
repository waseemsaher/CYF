<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Models\Course;
use App\Models\User;

class AssignTeacherToCourse
{
    public function handle(Course $course, User $teacher, ?int $sharePercent = null): void
    {
        if (! $teacher->hasRole('teacher')) {
            $teacher->assignRole('teacher');
        }

        $course->teachers()->syncWithoutDetaching([
            $teacher->id => ['teacher_share_percent' => $sharePercent],
        ]);

        activity('course')
            ->performedOn($course)
            ->withProperties([
                'teacher_id' => $teacher->id,
                'teacher_share_percent' => $sharePercent,
            ])
            ->log('teacher_assigned');
    }
}
