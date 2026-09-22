<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Actions;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Setting;
use App\Models\Term;
use App\Models\User;

class GrantEnrollment
{
    public function handle(User $student, Course $course, Term $term, User $admin): Enrollment
    {
        $graceDays = (int) Setting::getValue('enrollment', 'grace_days', 0);

        $enrollment = Enrollment::create([
            'user_id' => $student->getKey(),
            'course_id' => $course->getKey(),
            'term_id' => $term->getKey(),
            'source' => 'admin_grant',
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => $term->getAttribute('ends_at')->addDays($graceDays),
            'granted_by' => $admin->getKey(),
        ]);

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

        return $enrollment;
    }
}
