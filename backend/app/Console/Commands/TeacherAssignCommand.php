<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Actions\AssignTeacherToCourse;
use App\Models\Course;
use App\Models\User;
use Illuminate\Console\Command;

class TeacherAssignCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'teacher:assign
                            {teacher : Teacher ID or email address}
                            {course : Course ID or slug}
                            {--share=70 : Teacher revenue share percentage}';

    /**
     * @var string
     */
    protected $description = 'Assign a teacher to a course with a defined revenue share';

    public function handle(AssignTeacherToCourse $assignAction): int
    {
        $teacherInput = (string) $this->argument('teacher');
        $courseInput = (string) $this->argument('course');
        $sharePercent = (int) $this->option('share');

        if ($sharePercent < 0 || $sharePercent > 100) {
            $this->error('Revenue share must be between 0 and 100 percent.');

            return self::FAILURE;
        }

        $teacher = User::query()
            ->where('email', $teacherInput)
            ->orWhere('id', is_numeric($teacherInput) ? (int) $teacherInput : 0)
            ->first();

        if (! $teacher) {
            $this->error("Teacher not found with identifier: [{$teacherInput}]");

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

        $assignAction->handle($course, $teacher, $sharePercent);

        $this->info("Assigned teacher [{$teacher->name} ({$teacher->email})] to course [{$course->slug}] with {$sharePercent}% revenue share.");

        return self::SUCCESS;
    }
}
