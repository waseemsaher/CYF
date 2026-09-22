<?php

declare(strict_types=1);

namespace App\Domain\Learning\Actions;

use App\Models\Course;
use App\Models\CourseSection;

class CreateSection
{
    /**
     * @param  array{ar: string, en: string}  $title
     */
    public function handle(Course $course, array $title, ?int $position = null): CourseSection
    {
        if ($position === null) {
            $maxPos = CourseSection::query()->where('course_id', $course->id)->max('position');
            $position = $maxPos !== null ? ((int) $maxPos + 1) : 0;
        }

        return CourseSection::create([
            'course_id' => $course->id,
            'title' => $title,
            'position' => $position,
        ]);
    }
}
