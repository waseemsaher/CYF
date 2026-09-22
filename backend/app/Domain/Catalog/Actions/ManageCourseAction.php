<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Models\Course;

class ManageCourseAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Course
    {
        return Course::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Course $course, array $attributes): Course
    {
        $course->fill($attributes);
        $course->save();

        return $course->refresh();
    }

    public function delete(Course $course): void
    {
        $course->delete();
    }
}
