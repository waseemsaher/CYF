<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Learning\Actions\CreateCourseItem;
use App\Domain\Learning\Actions\CreateSection;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseItem;
use App\Models\CourseSection;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLearningController extends Controller
{
    // ================= Sections =================

    public function storeSection(Request $request, int $courseId, CreateSection $action): JsonResponse
    {
        $this->authorizeManage($request->user());

        $request->validate([
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer'],
        ]);

        /** @var Course $course */
        $course = Course::query()->findOrFail($courseId);

        $section = $action->handle(
            $course,
            $request->input('title'),
            $request->input('position')
        );

        return response()->json(['data' => $section], 201);
    }

    public function updateSection(Request $request, int $sectionId): JsonResponse
    {
        $this->authorizeManage($request->user());

        $request->validate([
            'title' => ['nullable', 'array'],
            'title.ar' => ['nullable', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer'],
        ]);

        /** @var CourseSection $section */
        $section = CourseSection::query()->findOrFail($sectionId);

        if ($request->has('title')) {
            $section->title = $request->input('title');
        }
        if ($request->has('position')) {
            $section->position = (int) $request->input('position');
        }

        $section->save();

        return response()->json(['data' => $section]);
    }

    public function destroySection(Request $request, int $sectionId): JsonResponse
    {
        $this->authorizeManage($request->user());

        /** @var CourseSection $section */
        $section = CourseSection::query()->findOrFail($sectionId);
        $section->delete();

        return response()->json(['message' => 'Section deleted successfully.']);
    }

    // ================= Items =================

    public function storeItem(Request $request, int $courseId, int $sectionId, CreateCourseItem $action): JsonResponse
    {
        $this->authorizeManage($request->user());

        $request->validate([
            'type' => ['required', 'in:lecture_link,external_link,file,quiz,exam,text'],
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'url' => ['nullable', 'url', 'max:2048'],
            'file' => ['nullable', 'file', 'max:51200'], // 50MB
            'quiz_id' => ['nullable', 'exists:quizzes,id'],
            'position' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        /** @var Course $course */
        $course = Course::query()->findOrFail($courseId);
        /** @var CourseSection $section */
        $section = CourseSection::query()
            ->where('id', $sectionId)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $item = $action->handle(
            $course,
            $section,
            $request->input('type'),
            $request->input('title'),
            $request->input('description'),
            $request->input('url'),
            $request->file('file'),
            $request->input('quiz_id') ? (int) $request->input('quiz_id') : null,
            $request->input('position') ? (int) $request->input('position') : null,
            $request->boolean('is_published', true)
        );

        return response()->json(['data' => $item], 201);
    }

    public function updateItem(Request $request, int $itemId): JsonResponse
    {
        $this->authorizeManage($request->user());

        /** @var CourseItem $item */
        $item = CourseItem::query()->findOrFail($itemId);

        $request->validate([
            'title' => ['nullable', 'array'],
            'description' => ['nullable', 'array'],
            'url' => ['nullable', 'url', 'max:2048'],
            'telegram_message_id' => ['nullable', 'integer', 'min:1'],
            'position' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($request->has('title')) {
            $item->title = $request->input('title');
        }
        if ($request->has('description')) {
            $item->description = $request->input('description');
        }
        if ($request->has('url')) {
            $item->url = $request->input('url');
        }
        if ($request->has('telegram_message_id')) {
            $item->telegram_message_id = $request->input('telegram_message_id');
        }
        if ($request->has('position')) {
            $item->position = (int) $request->input('position');
        }
        if ($request->has('is_published')) {
            $item->is_published = $request->boolean('is_published');
        }

        $item->save();

        return response()->json(['data' => $item]);
    }

    public function destroyItem(Request $request, int $itemId): JsonResponse
    {
        $this->authorizeManage($request->user());

        /** @var CourseItem $item */
        $item = CourseItem::query()->findOrFail($itemId);
        $item->delete();

        return response()->json(['message' => 'Course item deleted successfully.']);
    }

    // ================= Quizzes =================

    public function storeQuiz(Request $request, int $courseId): JsonResponse
    {
        $this->authorizeManage($request->user());

        $request->validate([
            'kind' => ['required', 'in:quiz,exam'],
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            'results_visibility' => ['nullable', 'in:immediate,after_close,hidden'],
        ]);

        $quiz = Quiz::create([
            'course_id' => $courseId,
            'kind' => $request->input('kind'),
            'title' => $request->input('title'),
            'duration_minutes' => $request->input('duration_minutes'),
            'max_attempts' => (int) $request->input('max_attempts', 1),
            'available_from' => $request->input('available_from'),
            'available_until' => $request->input('available_until'),
            'shuffle_questions' => $request->boolean('shuffle_questions', false),
            'shuffle_options' => $request->boolean('shuffle_options', false),
            'results_visibility' => $request->input('results_visibility', 'immediate'),
        ]);

        return response()->json(['data' => $quiz], 201);
    }

    public function storeQuestion(Request $request, int $quizId): JsonResponse
    {
        $this->authorizeManage($request->user());

        $request->validate([
            'type' => ['required', 'in:mcq,true_false'],
            'text' => ['required', 'array'],
            'text.ar' => ['required', 'string'],
            'text.en' => ['required', 'string'],
            'explanation' => ['nullable', 'array'],
            'points' => ['nullable', 'integer', 'min:1'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.text' => ['required', 'array'],
            'options.*.is_correct' => ['required', 'boolean'],
        ]);

        /** @var Quiz $quiz */
        $quiz = Quiz::query()->findOrFail($quizId);

        $maxPos = $quiz->questions()->max('position');
        $position = $maxPos !== null ? ((int) $maxPos + 1) : 0;

        /** @var Question $question */
        $question = $quiz->questions()->create([
            'type' => $request->input('type'),
            'text' => $request->input('text'),
            'explanation' => $request->input('explanation'),
            'points' => (int) $request->input('points', 1),
            'position' => $position,
        ]);

        foreach ($request->input('options') as $optionData) {
            $question->options()->create([
                'text' => $optionData['text'],
                'is_correct' => (bool) $optionData['is_correct'],
            ]);
        }

        return response()->json(['data' => $question->load('options')], 201);
    }

    public function destroyQuestion(Request $request, int $questionId): JsonResponse
    {
        $this->authorizeManage($request->user());

        /** @var Question $question */
        $question = Question::query()->findOrFail($questionId);
        $question->delete();

        return response()->json(['message' => 'Question deleted successfully.']);
    }

    private function authorizeManage(?User $user): void
    {
        if (! $user || (! $user->hasRole(['superadmin', 'admin']) && ! $user->can('courses.manage'))) {
            abort(403, 'Unauthorized.');
        }
    }
}
