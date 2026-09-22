<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Catalog\Actions\CalculateCoursePrice;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Term;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function index(CalculateCoursePrice $calculateCoursePrice): JsonResponse
    {
        $query = Course::query()
            ->where('status', 'published');

        $academicYearId = request()->query('academic_year_id');
        $departmentId = request()->query('department_id');

        if (is_string($academicYearId) && $academicYearId !== '') {
            $query->whereHas('audiences', function ($audienceQuery) use ($academicYearId): void {
                $audienceQuery->where('academic_year_id', (int) $academicYearId);
            });
        }

        if (is_string($departmentId) && $departmentId !== '') {
            $query->whereHas('audiences', function ($audienceQuery) use ($departmentId): void {
                $audienceQuery->where('department_id', (int) $departmentId);
            });
        }

        $courses = $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(12);

        $payload = [];

        foreach ($courses->getCollection() as $course) {
            $pricing = $calculateCoursePrice->handle($course);

            $payload[] = [
                'id' => $course->getKey(),
                'slug' => $course->getAttribute('slug'),
                'title' => $course->getTranslations('title'),
                'description' => $course->getTranslations('description'),
                'price_cents' => (int) $course->getAttribute('price_cents'),
                ...$pricing,
                'status' => $course->getAttribute('status'),
                'sort_order' => (int) $course->getAttribute('sort_order'),
            ];
        }

        return response()->json([
            'data' => $payload,
            'meta' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
        ]);
    }

    public function show(string $slug, CalculateCoursePrice $calculateCoursePrice): JsonResponse
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

        $pricing = $calculateCoursePrice->handle($course);

        return response()->json([
            'data' => [
                'id' => $course->getKey(),
                'slug' => $course->getAttribute('slug'),
                'title' => $course->getTranslations('title'),
                'description' => $course->getTranslations('description'),
                'price_cents' => (int) $course->getAttribute('price_cents'),
                ...$pricing,
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
        ])->header('Cache-Control', 'public, max-age=3600, stale-while-revalidate=86400');
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
        ])->header('Cache-Control', 'public, max-age=3600, stale-while-revalidate=86400');
    }

    public function terms(): JsonResponse
    {
        /** @var Collection<int, Term> $terms */
        $terms = Term::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $terms->map(function (Term $term): array {
                return [
                    'id' => $term->getKey(),
                    'name' => $term->getTranslations('name'),
                    'starts_at' => $term->getAttribute('starts_at')?->toISOString(),
                    'ends_at' => $term->getAttribute('ends_at')?->toISOString(),
                    'is_current' => (bool) $term->getAttribute('is_current'),
                    'sort_order' => (int) $term->getAttribute('sort_order'),
                ];
            })->all(),
        ])->header('Cache-Control', 'public, max-age=3600, stale-while-revalidate=86400');
    }
}
