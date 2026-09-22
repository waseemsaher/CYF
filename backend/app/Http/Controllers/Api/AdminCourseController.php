<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Catalog\Actions\ManageCourseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminCourseController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Course::class);

        $courses = Course::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(12);

        return response()->json([
            'data' => $courses->getCollection()->map(fn (Course $course): array => $this->payload($course))->all(),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
        ]);
    }

    public function store(StoreCourseRequest $request, ManageCourseAction $manageCourse): JsonResponse
    {
        Gate::authorize('manage', Course::class);

        $course = $manageCourse->create($request->validated());

        return response()->json(['data' => $this->payload($course)], 201);
    }

    public function update(UpdateCourseRequest $request, Course $course, ManageCourseAction $manageCourse): JsonResponse
    {
        Gate::authorize('update', $course);

        $course = $manageCourse->update($course, $request->validated());

        return response()->json(['data' => $this->payload($course)]);
    }

    public function destroy(Course $course, ManageCourseAction $manageCourse): JsonResponse
    {
        Gate::authorize('delete', $course);
        $manageCourse->delete($course);

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Course $course): array
    {
        return [
            'id' => $course->getKey(),
            'slug' => $course->getAttribute('slug'),
            'title' => $course->getTranslations('title'),
            'description' => $course->getTranslations('description'),
            'cover_image_path' => $course->getAttribute('cover_image_path'),
            'price_cents' => (int) $course->getAttribute('price_cents'),
            'status' => $course->getAttribute('status'),
            'telegram_chat_id' => $course->getAttribute('telegram_chat_id'),
            'telegram_invite_link' => $course->getAttribute('telegram_invite_link'),
            'teacher_share_percent' => $course->getAttribute('teacher_share_percent'),
            'sort_order' => (int) $course->getAttribute('sort_order'),
        ];
    }
}
