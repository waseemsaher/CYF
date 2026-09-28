<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Enrollment\Actions\GrantEnrollment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Console\Command;

class EnrollmentGrantCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'enrollment:grant
                            {email : Student email address}
                            {course : Course slug or ID}
                            {--term=current : Term ID or "current"}';

    /**
     * @var string
     */
    protected $description = 'Manually grant course enrollment to a student';

    public function handle(GrantEnrollment $grantAction): int
    {
        $email = trim((string) $this->argument('email'));
        $courseInput = trim((string) $this->argument('course'));
        $termOption = (string) $this->option('term');

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

        if ($termOption === 'current') {
            $term = Term::where('is_current', true)->first();
            if (! $term) {
                $term = Term::latest('id')->first();
            }
        } else {
            $term = Term::query()
                ->where('id', is_numeric($termOption) ? (int) $termOption : 0)
                ->first();
        }

        if (! $term) {
            $this->error('No valid academic term found to associate with this enrollment.');

            return self::FAILURE;
        }

        // Idempotency: check existing active enrollment
        $existingEnrollment = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->first();

        if ($existingEnrollment !== null) {
            $this->warn("Student [{$student->email}] already has an active enrollment in course [{$course->slug}].");

            return self::SUCCESS;
        }

        /** @var User|null $admin */
        $admin = User::role('superadmin')->first()
            ?? User::role('admin')->first()
            ?? $student;

        $enrollment = $grantAction->handle($student, $course, $term, $admin);

        $this->info("Enrollment granted successfully (ID: {$enrollment->id}).");
        $this->line("  Student: {$student->name} ({$student->email})");
        $this->line("  Course:  {$course->slug}");
        $expiresAt = $enrollment->getAttribute('expires_at');
        $expiresDisplay = $expiresAt instanceof \DateTimeInterface ? $expiresAt->format('Y-m-d H:i:s') : (string) $expiresAt;
        $this->line("  Expires: {$expiresDisplay}");

        return self::SUCCESS;
    }
}
