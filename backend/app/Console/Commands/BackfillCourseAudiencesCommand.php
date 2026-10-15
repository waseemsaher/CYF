<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Console\Command;

class BackfillCourseAudiencesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'courses:backfill-audiences';

    /**
     * @var string
     */
    protected $description = 'Backfill existing courses without audiences and not general to Level 1 / First Year for all departments';

    public function handle(): int
    {
        /** @var AcademicYear|null $firstYear */
        $firstYear = AcademicYear::query()->where('name->ar', 'السنة الأولى')->first();

        if (! $firstYear instanceof AcademicYear) {
            $this->error("Academic year 'السنة الأولى' not found.");

            return self::FAILURE;
        }

        $departments = Department::all();

        if ($departments->isEmpty()) {
            $this->error('No departments found.');

            return self::FAILURE;
        }

        $coursesToBackfill = Course::query()
            ->where('is_general', false)
            ->whereDoesntHave('audiences')
            ->get();

        if ($coursesToBackfill->isEmpty()) {
            $this->info('No courses need audience backfilling.');

            return self::SUCCESS;
        }

        $this->info("Found {$coursesToBackfill->count()} course(s) to backfill to Level 1 (First Year):");

        foreach ($coursesToBackfill as $course) {
            $titleAr = $course->getTranslation('title', 'ar');
            $this->line("- ID: {$course->getKey()} | Slug: {$course->getAttribute('slug')} | Title: {$titleAr}");

            $audienceRows = [];
            foreach ($departments as $department) {
                $audienceRows[] = [
                    'academic_year_id' => $firstYear->getKey(),
                    'department_id' => $department->getKey(),
                ];
            }

            $course->audiences()->createMany($audienceRows);
        }

        $this->info("Successfully backfilled {$coursesToBackfill->count()} course(s) to Level 1 across all {$departments->count()} departments.");

        return self::SUCCESS;
    }
}
