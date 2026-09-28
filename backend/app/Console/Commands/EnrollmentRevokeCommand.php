<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Enrollment\Actions\RevokeEnrollment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Console\Command;

class EnrollmentRevokeCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'enrollment:revoke
                            {email : Student email address}
                            {course : Course slug or ID}';

    /**
     * @var string
     */
    protected $description = 'Manually revoke course enrollment for a student';

    public function handle(RevokeEnrollment $revokeAction): int
    {
        $email = trim((string) $this->argument('email'));
        $courseInput = trim((string) $this->argument('course'));

        $student = User::where('email', $email)->first();
        if (! $student) {
            $this->error("Student not found with email: [{$email}]");

            return self::FAILURE;
        }

        $course = Course::query()
            ->where('slug', $courseInput)
            ->orWhere('id', is_numeric($courseInput) ? (int) $courseInput : 0)
            ->first();

        if (! $course) {
            $this->error("Course not found with identifier: [{$courseInput}]");

            return self::FAILURE;
        }

        $enrollment = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            $this->warn("No active enrollment found for student [{$student->email}] in course [{$course->slug}].");

            return self::SUCCESS;
        }

        /** @var User|null $admin */
        $admin = User::role('superadmin')->first()
            ?? User::role('admin')->first()
            ?? $student;

        $revokeAction->handle($enrollment, $admin);

        $this->info("Enrollment revoked successfully for student [{$student->email}] in course [{$course->slug}].");

        return self::SUCCESS;
    }
}
