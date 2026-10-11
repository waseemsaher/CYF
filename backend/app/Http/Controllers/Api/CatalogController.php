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
use Illuminate\Support\Facades\Log;

class CatalogController extends Controller
{
    public function index(CalculateCoursePrice $calculateCoursePrice): JsonResponse
    {
        $query = Course::query()
            ->where('status', 'published');

        $academicYearId = request()->query('academic_year_id');
        $departmentId = request()->query('department_id');

        $hasYear = is_string($academicYearId) && $academicYearId !== '';
        $hasDept = is_string($departmentId) && $departmentId !== '';

        if ($hasYear || $hasDept) {
            $query->where(function ($sub) use ($hasYear, $academicYearId, $hasDept, $departmentId): void {
                $sub->where('is_general', true)
                    ->orWhere(function ($audienceSub) use ($hasYear, $academicYearId, $hasDept, $departmentId): void {
                        $audienceSub->whereHas('audiences', function ($audienceQuery) use ($hasYear, $academicYearId, $hasDept, $departmentId): void {
                            if ($hasYear) {
                                $audienceQuery->where('academic_year_id', (int) $academicYearId);
                            }
                            if ($hasDept) {
                                $audienceQuery->where('department_id', (int) $departmentId);
                            }
                        });
                    });
            });
        }

        // Flag courses that are neither general nor have audience rows configured
        $unconfiguredCourses = Course::query()
            ->where('status', 'published')
            ->where('is_general', false)
            ->whereDoesntHave('audiences')
            ->pluck('slug')
            ->all();

        if (! empty($unconfiguredCourses)) {
            Log::warning('Catalog contains published course(s) with neither is_general nor audience rows: '.implode(', ', $unconfiguredCourses));
        }

        $courses = $query
            ->with(['audiences.academicYear', 'audiences.department'])
            ->withCount([
                'items as lessons_count' => function ($q): void {
                    $q->whereIn('type', ['lecture_link', 'file']);
                },
                'quizzes as quizzes_count',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(12);

        $payload = [];

        foreach ($courses->getCollection() as $course) {
            $pricing = $calculateCoursePrice->handle($course);

            $payload[] = $this->formatCoursePayload($course, $pricing);
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
            ->with(['audiences.academicYear', 'audiences.department'])
            ->withCount([
                'items as lessons_count' => function ($q): void {
                    $q->whereIn('type', ['lecture_link', 'file']);
                },
                'quizzes as quizzes_count',
            ])
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
            'data' => $this->formatCoursePayload($course, $pricing),
        ]);
    }

    /**
     * @param  array{list_price_cents: int, discount_cents: int, amount_due_cents: int, discount_id: int|null}  $pricing
     * @return array<string, mixed>
     */
    private function formatCoursePayload(Course $course, array $pricing): array
    {
        $audiences = $course->audiences->map(function ($aud): array {
            return [
                'academic_year_id' => (int) $aud->academic_year_id,
                'department_id' => (int) $aud->department_id,
                'academic_year_name' => $aud->academicYear ? $aud->academicYear->getTranslations('name') : null,
                'department_name' => $aud->department ? $aud->department->getTranslations('name') : null,
                'department_code' => $aud->department ? $aud->department->getAttribute('code') : null,
            ];
        })->all();

        return [
            'id' => $course->getKey(),
            'slug' => $course->getAttribute('slug'),
            'title' => $course->getTranslations('title'),
            'description' => $course->getTranslations('description'),
            'price_cents' => (int) $course->getAttribute('price_cents'),
            ...$pricing,
            'status' => $course->getAttribute('status'),
            'is_general' => (bool) $course->getAttribute('is_general'),
            'audiences' => $audiences,
            'lessons_count' => (int) ($course->getAttribute('lessons_count') ?? 0),
            'quizzes_count' => (int) ($course->getAttribute('quizzes_count') ?? 0),
            'sort_order' => (int) $course->getAttribute('sort_order'),
        ];
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
