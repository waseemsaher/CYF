<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var Collection<int, Course> $courses */
        $courses = Course::query()
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $payload = [];

        foreach ($courses as $course) {
            $payload[] = [
                'id' => $course->getKey(),
                'slug' => $course->getAttribute('slug'),
                'title' => $course->getTranslations('title'),
                'description' => $course->getTranslations('description'),
                'price_cents' => (int) $course->getAttribute('price_cents'),
                'status' => $course->getAttribute('status'),
                'sort_order' => (int) $course->getAttribute('sort_order'),
            ];
        }

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        /** @var Course|null $course */
        $course = Course::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (! $course instanceof Course) {
            return response()->json([
                'message' => 'Course not found.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $course->getKey(),
                'slug' => $course->getAttribute('slug'),
                'title' => $course->getTranslations('title'),
                'description' => $course->getTranslations('description'),
                'price_cents' => (int) $course->getAttribute('price_cents'),
                'status' => $course->getAttribute('status'),
                'sort_order' => (int) $course->getAttribute('sort_order'),
            ],
        ]);
    }

    public function academicYears(): JsonResponse
    {
        /** @var Collection<int, AcademicYear> $years */
        $years = AcademicYear::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $years->map(function (AcademicYear $year): array {
                return [
                    'id' => $year->getKey(),
                    'name' => $year->getTranslations('name'),
                    'sort_order' => (int) $year->getAttribute('sort_order'),
                ];
            })->all(),
        ]);
    }

    public function departments(): JsonResponse
    {
        /** @var Collection<int, Department> $departments */
        $departments = Department::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $departments->map(function (Department $department): array {
                return [
                    'id' => $department->getKey(),
                    'code' => $department->getAttribute('code'),
                    'name' => $department->getTranslations('name'),
                    'sort_order' => (int) $department->getAttribute('sort_order'),
                ];
            })->all(),
        ]);
    }
}
