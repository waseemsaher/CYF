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
        $audiences = $attributes['audiences'] ?? null;
        unset($attributes['audiences']);

        $course = Course::query()->create($attributes);

        if (is_array($audiences)) {
            $this->syncAudiences($course, $audiences);
        }

        return $course->load('audiences');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Course $course, array $attributes): Course
    {
        $audiences = $attributes['audiences'] ?? null;
        unset($attributes['audiences']);

        $course->fill($attributes);
        $course->save();

        if (is_array($audiences)) {
            $this->syncAudiences($course, $audiences);
        }

        return $course->refresh()->load('audiences');
    }

    public function delete(Course $course): void
    {
        $course->delete();
    }

    /**
     * @param  array<int, array{academic_year_id: int, department_id: int}>  $audiences
     */
    private function syncAudiences(Course $course, array $audiences): void
    {
        $course->audiences()->delete();
        $course->audiences()->createMany($audiences);
    }
}
