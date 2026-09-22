<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Identity\Actions\CalculateTeacherBalance;
use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TeacherPayout;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    /**
     * Teacher dashboard overview.
     */
    public function dashboard(Request $request, CalculateTeacherBalance $calculateBalance): JsonResponse
    {
        /** @var User $teacher */
        $teacher = $request->user();
        $this->authorizeTeacher($teacher);

        $earnings = $calculateBalance->handle($teacher);

        $courses = $teacher->taughtCourses()->withCount(['audiences'])->get()->map(function (Course $c) {
            $studentsCount = Enrollment::query()
                ->where('course_id', $c->id)
                ->where('status', 'active')
                ->where(function ($q): void {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->count();

            return [
                'id' => $c->id,
                'slug' => $c->slug,
                'title' => $c->getTranslations('title'),
                'teacher_share_percent' => $c->pivot ? $c->pivot->getAttribute('teacher_share_percent') : $c->teacher_share_percent,
                'active_students_count' => $studentsCount,
            ];
        });

        $recentPayouts = $teacher->payouts()
            ->orderByDesc('paid_at')
            ->limit(10)
            ->get()
            ->map(fn (TeacherPayout $p): array => [
                'id' => $p->id,
                'amount_cents' => $p->amount_cents,
                'paid_at' => $p->paid_at->toIso8601String(),
                'note' => $p->note,
            ])
            ->all();

        return response()->json([
            'data' => [
                'earnings' => $earnings,
                'courses' => $courses,
                'payouts' => $recentPayouts,
            ],
        ]);
    }

    /**
     * Read-only enrolled student list for teacher's course.
     */
    public function courseStudents(Request $request, int $courseId): JsonResponse
    {
        /** @var User $teacher */
        $teacher = $request->user();
        $this->authorizeCourseTeacher($teacher, $courseId);

        $enrollments = Enrollment::query()
            ->with(['user'])
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->paginate(25);

        return response()->json([
            'data' => $enrollments->items(),
            'meta' => [
                'current_page' => $enrollments->currentPage(),
                'last_page' => $enrollments->lastPage(),
                'total' => $enrollments->total(),
            ],
        ]);
    }

    /**
     * Analytics for a quiz (average score, per-question correct rate).
     */
    public function quizAnalytics(Request $request, int $quizId): JsonResponse
    {
        /** @var Quiz $quiz */
        $quiz = Quiz::query()->with('questions.options')->findOrFail($quizId);

        /** @var User $teacher */
        $teacher = $request->user();
        $this->authorizeCourseTeacher($teacher, $quiz->course_id);

        $attempts = QuizAttempt::query()
            ->with('user')
            ->where('quiz_id', $quiz->id)
            ->where('status', 'submitted')
            ->get();

        $totalAttempts = $attempts->count();
        $averageScore = $totalAttempts > 0 ? round($attempts->avg('score'), 1) : 0;
        $maxScore = $quiz->questions->sum('points');

        // Per-question correct rate
        $questionsStats = [];
        foreach ($quiz->questions as $question) {
            $answersCount = AttemptAnswer::query()
                ->where('question_id', $question->id)
                ->whereHas('attempt', fn ($q) => $q->where('quiz_id', $quiz->id)->where('status', 'submitted'))
                ->count();

            $correctAnswersCount = AttemptAnswer::query()
                ->where('question_id', $question->id)
                ->where('is_correct', true)
                ->whereHas('attempt', fn ($q) => $q->where('quiz_id', $quiz->id)->where('status', 'submitted'))
                ->count();

            $accuracyRate = $answersCount > 0 ? round(($correctAnswersCount / $answersCount) * 100, 1) : 0;

            $questionsStats[] = [
                'question_id' => $question->id,
                'text' => $question->getTranslations('text'),
                'points' => $question->points,
                'total_answers' => $answersCount,
                'correct_answers' => $correctAnswersCount,
                'accuracy_rate' => $accuracyRate,
            ];
        }

        return response()->json([
            'data' => [
                'quiz' => [
                    'id' => $quiz->id,
                    'title' => $quiz->getTranslations('title'),
                    'max_score' => $maxScore,
                ],
                'stats' => [
                    'total_attempts' => $totalAttempts,
                    'average_score' => $averageScore,
                    'average_percentage' => $maxScore > 0 ? round(($averageScore / $maxScore) * 100, 1) : 0,
                ],
                'questions' => $questionsStats,
                'attempts' => $attempts->map(fn (QuizAttempt $att): array => [
                    'id' => $att->id,
                    'student_name' => $att->user->name,
                    'student_email' => $att->user->email,
                    'score' => $att->score,
                    'max_score' => $att->max_score,
                    'submitted_at' => $att->submitted_at?->toIso8601String(),
                ])->all(),
            ],
        ]);
    }

    private function authorizeTeacher(User $user): void
    {
        if (! $user->hasRole(['teacher', 'superadmin', 'admin'])) {
            abort(403, 'Unauthorized.');
        }
    }

    private function authorizeCourseTeacher(User $user, int $courseId): void
    {
        if ($user->hasRole(['superadmin', 'admin']) || $user->can('courses.manage')) {
            return;
        }

        $teachesCourse = $user->taughtCourses()->where('courses.id', $courseId)->exists();
        if (! $teachesCourse) {
            abort(403, 'You do not teach this course.');
        }
    }
}
