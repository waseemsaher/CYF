<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Learning\Actions\GetQuizAttemptResult;
use App\Domain\Learning\Actions\GradeQuizAttempt;
use App\Domain\Learning\Actions\StartQuizAttempt;
use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    /**
     * Start a new attempt for a quiz.
     */
    public function start(Request $request, int $quizId, StartQuizAttempt $startQuizAttempt): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Quiz $quiz */
        $quiz = Quiz::query()->findOrFail($quizId);

        $result = $startQuizAttempt->handle($quiz, $user);

        return response()->json([
            'data' => [
                'attempt' => [
                    'id' => $result['attempt']->id,
                    'status' => $result['attempt']->status,
                    'started_at' => $result['attempt']->started_at->toIso8601String(),
                    'duration_minutes' => $quiz->duration_minutes,
                ],
                'quiz' => [
                    'id' => $quiz->id,
                    'title' => $quiz->getTranslations('title'),
                    'kind' => $quiz->kind,
                ],
                'questions' => $result['questions'],
            ],
        ]);
    }

    /**
     * Submit an in-progress attempt for grading.
     */
    public function submit(
        Request $request,
        int $quizId,
        int $attemptId,
        GradeQuizAttempt $gradeQuizAttempt,
        GetQuizAttemptResult $getResult,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        /** @var QuizAttempt $attempt */
        $attempt = QuizAttempt::query()
            ->where('id', $attemptId)
            ->where('quiz_id', $quizId)
            ->firstOrFail();

        if ($attempt->user_id !== $user->id) {
            abort(403, 'غير مصرح لك بتسليم محاولة طالب آخر.');
        }

        $answers = $request->input('answers', []);
        $gradedAttempt = $gradeQuizAttempt->handle($attempt, $answers);

        $result = $getResult->handle($gradedAttempt, $user);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * View attempt results.
     */
    public function showAttempt(
        Request $request,
        int $quizId,
        int $attemptId,
        GetQuizAttemptResult $getResult,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        /** @var QuizAttempt $attempt */
        $attempt = QuizAttempt::query()
            ->where('id', $attemptId)
            ->where('quiz_id', $quizId)
            ->firstOrFail();

        $isStaff = $user->hasRole(['superadmin', 'admin']) || $user->can('courses.manage');
        if (! $isStaff && $attempt->user_id !== $user->id) {
            abort(403, 'غير مصرح لك بالاطلاع على محاولة طالب آخر.');
        }

        $result = $getResult->handle($attempt, $user);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * List current student's past attempts for this quiz.
     */
    public function myAttempts(Request $request, int $quizId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $attempts = QuizAttempt::query()
            ->where('quiz_id', $quizId)
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $attempts->map(fn (QuizAttempt $att): array => [
                'id' => $att->id,
                'status' => $att->status,
                'started_at' => $att->started_at->toIso8601String(),
                'submitted_at' => $att->submitted_at?->toIso8601String(),
                'score' => $att->score,
                'max_score' => $att->max_score,
            ])->all(),
        ]);
    }
}
