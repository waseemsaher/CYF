<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Actions;

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

            return $enrollment;
        });
    }
}
