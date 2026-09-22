<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Actions;

use App\Models\Enrollment;
use App\Models\User;

class RevokeEnrollment
{
    public function handle(Enrollment $enrollment, User $admin): Enrollment
    {
        $enrollment->update([
            'status' => 'revoked',
        ]);

        activity('enrollment')
            ->performedOn($enrollment)
            ->causedBy($admin)
            ->withProperties([
                'enrollment_id' => $enrollment->getKey(),
                'student_id' => $enrollment->getAttribute('user_id'),
                'course_id' => $enrollment->getAttribute('course_id'),
            ])
            ->log('enrollment_revoked');

        return $enrollment;
    }
}
