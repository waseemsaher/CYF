<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Learning\Actions\CreateCourseItem;
use App\Domain\Learning\Actions\CreateSection;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseItem;
use App\Models\CourseSection;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherCourseContentController extends Controller
{
    /**
     * Get course content outline for the course editor.
     */
    public function show(Request $request, int $courseId): JsonResponse
    {
        /** @var User $teacher */
        $teacher = $request->user();
        $this->authorizeCourseTeacher($teacher, $courseId);

        /** @var Course $course */
        $course = Course::query()
            ->with(['sections.items'])
            ->findOrFail($courseId);

        $sectionsData = [];
        foreach ($course->sections as $section) {
            $itemsData = [];
            foreach ($section->items as $item) {
                $itemsData[] = [
                    'id' => $item->id,
                    'type' => $item->type,
                    'title' => $item->getTranslations('title'),
                    'description' => $item->getTranslations('description'),
                    'url' => $item->url,
                    'file_path' => $item->file_path,
                    'telegram_message_id' => $item->telegram_message_id,
                    'is_free' => (bool) $item->is_free,
                    'is_published' => (bool) $item->is_published,
                    'position' => $item->position,
                ];
            }

            $sectionsData[] = [
                'id' => $section->id,
                'title' => $section->getTranslations('title'),
                'position' => $section->position,
                'items' => $itemsData,
            ];
        }

        return response()->json([
            'data' => [
                'course' => [
                    'id' => $course->id,
                    'slug' => $course->slug,
                    'title' => $course->getTranslations('title'),
                ],
                'sections' => $sectionsData,
            ],
        ]);
    }

    public function storeSection(Request $request, int $courseId, CreateSection $action): JsonResponse
    {
        /** @var User $teacher */
        $teacher = $request->user();
        $this->authorizeCourseTeacher($teacher, $courseId);

        $request->validate([
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer'],
        ]);

        /** @var Course $course */
        $course = Course::query()->findOrFail($courseId);

        $title = [
            'ar' => $request->input('title.ar'),
            'en' => $request->input('title.en') ?: $request->input('title.ar'),
        ];

        $section = $action->handle(
            $course,
            $title,
            $request->input('position') ? (int) $request->input('position') : null
        );

        return response()->json(['data' => $section], 201);
    }

    public function updateSection(Request $request, int $courseId, int $sectionId): JsonResponse
    {
        /** @var User $teacher */
        $teacher = $request->user();
        $this->authorizeCourseTeacher($teacher, $courseId);

        $request->validate([
            'title' => ['nullable', 'array'],
            'title.ar' => ['nullable', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer'],
        ]);

        /** @var CourseSection $section */
        $section = CourseSection::query()
            ->where('id', $sectionId)
            ->where('course_id', $courseId)
            ->firstOrFail();

        if ($request->has('title')) {
            $ar = $request->input('title.ar', $section->getTranslation('title', 'ar'));
            $en = $request->input('title.en', $section->getTranslation('title', 'en') ?: $ar);
            $section->title = ['ar' => $ar, 'en' => $en ?: $ar];
        }
        if ($request->has('position')) {
            $section->position = (int) $request->input('position');
        }

        $section->save();

        return response()->json(['data' => $section]);
    }

    public function destroySection(Request $request, int $courseId, int $sectionId): JsonResponse
    {
        /** @var User $teacher */
        $teacher = $request->user();
        $this->authorizeCourseTeacher($teacher, $courseId);

        /** @var CourseSection $section */
        $section = CourseSection::query()
            ->where('id', $sectionId)
            ->where('course_id', $courseId)
            ->firstOrFail();

        $section->delete();

        return response()->json(['message' => 'Section deleted successfully.']);
    }

    public function storeItem(Request $request, int $courseId, int $sectionId, CreateCourseItem $action): JsonResponse
    {
        /** @var User $teacher */
        $teacher = $request->user();
        $this->authorizeCourseTeacher($teacher, $courseId);

        $maxSizeKb = (int) Setting::getValue('uploads', 'teacher_file_max_size_kb', 51200);

        $request->validate([
            'type' => ['required', 'in:lecture_link,external_link,file,quiz,exam,text'],
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'url' => ['nullable', 'url', 'max:2048'],
            'file' => ['nullable', 'file', "max:{$maxSizeKb}"],
            'telegram_message_id' => ['nullable', 'integer', 'min:1'],
            'position' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
            'is_free' => ['nullable', 'boolean'],
        ]);

        /** @var Course $course */
        $course = Course::query()->findOrFail($courseId);
        /** @var CourseSection $section */
        $section = CourseSection::query()
            ->where('id', $sectionId)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $title = [
            'ar' => $request->input('title.ar'),
            'en' => $request->input('title.en') ?: $request->input('title.ar'),
        ];

        $item = $action->handle(
            $course,
            $section,
            $request->input('type'),
            $title,
            $request->input('description'),
            $request->input('url'),
            $request->file('file'),
            null,
            $request->input('position') ? (int) $request->input('position') : null,
            $request->boolean('is_published', true),
            $request->input('telegram_message_id') ? (int) $request->input('telegram_message_id') : null,
            $request->boolean('is_free', false),
        );

        return response()->json(['data' => $item], 201);
    }

    public function updateItem(Request $request, int $courseId, int $itemId): JsonResponse
    {
        /** @var User $teacher */
        $teacher = $request->user();
        $this->authorizeCourseTeacher($teacher, $courseId);

        /** @var CourseItem $item */
        $item = CourseItem::query()
            ->where('id', $itemId)
            ->where('course_id', $courseId)
            ->firstOrFail();

        $request->validate([
            'title' => ['nullable', 'array'],
            'title.ar' => ['nullable', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'url' => ['nullable', 'url', 'max:2048'],
            'telegram_message_id' => ['nullable', 'integer', 'min:1'],
            'position' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
            'is_free' => ['nullable', 'boolean'],
        ]);

        if ($request->has('title')) {
            $ar = $request->input('title.ar', $item->getTranslation('title', 'ar'));
            $en = $request->input('title.en', $item->getTranslation('title', 'en') ?: $ar);
            $item->title = ['ar' => $ar, 'en' => $en ?: $ar];
        }
        if ($request->has('description')) {
            $item->description = $request->input('description');
        }
        if ($request->has('url')) {
            $item->url = $request->input('url');
        }
        if ($request->has('telegram_message_id')) {
            $item->telegram_message_id = $request->input('telegram_message_id') ? (int) $request->input('telegram_message_id') : null;
        }
        if ($request->has('position')) {
            $item->position = (int) $request->input('position');
        }
        if ($request->has('is_published')) {
            $item->is_published = $request->boolean('is_published');
        }
        if ($request->has('is_free')) {
            $item->is_free = $request->boolean('is_free');
        }

        $item->save();

        return response()->json(['data' => $item]);
    }

    public function destroyItem(Request $request, int $courseId, int $itemId): JsonResponse
    {
        /** @var User $teacher */
        $teacher = $request->user();
        $this->authorizeCourseTeacher($teacher, $courseId);

        /** @var CourseItem $item */
        $item = CourseItem::query()
            ->where('id', $itemId)
            ->where('course_id', $courseId)
            ->firstOrFail();

        $item->delete();

        return response()->json(['message' => 'Item deleted successfully.']);
    }

    private function authorizeCourseTeacher(User $user, int $courseId): void
    {
        if ($user->hasRole(['superadmin', 'admin']) || $user->can('courses.manage')) {
            return;
        }

        if (! $user->hasRole('teacher')) {
            abort(403, 'Unauthorized.');
        }

        $teachesCourse = $user->taughtCourses()->where('courses.id', $courseId)->exists();
        if (! $teachesCourse) {
            abort(403, 'You do not teach this course.');
        }
    }
}

