<?php

declare(strict_types=1);

namespace App\Domain\Learning\Actions;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StartQuizAttempt
{
    /**
     * @return array{attempt: QuizAttempt, questions: list<array<string, mixed>>}
     */
    public function handle(Quiz $quiz, User $user): array
    {
        // 1. Check enrollment authorization (unless admin/superadmin/staff)
        $isStaff = $user->hasRole(['superadmin', 'admin']) || $user->can('courses.manage');
        if (! $isStaff) {
            $hasActiveEnrollment = Enrollment::query()
                ->where('user_id', $user->id)
                ->where('course_id', $quiz->course_id)
                ->where('status', 'active')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->exists();

            if (! $hasActiveEnrollment) {
                throw ValidationException::withMessages([
                    'quiz' => ['يجب أن تكون مشتركاً في هذه المادة لتتمكن من أداء الاختبار.'],
                ]);
            }
        }

        // 2. Check quiz availability window
        if (! $quiz->isAvailable()) {
            throw ValidationException::withMessages([
                'quiz' => ['الاختبار غير متاح حالياً.'],
            ]);
        }

        // 3. Check for existing in-progress attempt
        /** @var QuizAttempt|null $existingInProgress */
        $existingInProgress = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existingInProgress) {
            if ($existingInProgress->isExpired()) {
                $existingInProgress->update(['status' => 'expired']);
            } else {
                return [
                    'attempt' => $existingInProgress,
                    'questions' => $this->formatQuestionsForStudent($quiz),
                ];
            }
        }

        // 4. Check max attempts
        $completedAttemptsCount = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'expired'])
            ->count();

        if ($completedAttemptsCount >= $quiz->max_attempts) {
            throw ValidationException::withMessages([
                'quiz' => ['لقد استنفدت جميع المحاولات المتاحة لهذا الاختبار.'],
            ]);
        }

        // 5. Create new attempt
        $maxScore = (int) $quiz->questions()->sum('points');

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'started_at' => now(),
            'score' => 0,
            'max_score' => $maxScore,
            'status' => 'in_progress',
        ]);

        return [
            'attempt' => $attempt,
            'questions' => $this->formatQuestionsForStudent($quiz),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function formatQuestionsForStudent(Quiz $quiz): array
    {
        $questionsQuery = $quiz->questions()->with('options');

        $questions = $questionsQuery->get();

        if ($quiz->shuffle_questions) {
            $questions = $questions->shuffle();
        }

        $formatted = [];

        foreach ($questions as $question) {
            $options = $question->options;

            if ($quiz->shuffle_options) {
                $options = $options->shuffle();
            }

            $formattedOptions = [];
            foreach ($options as $option) {
                // NEVER expose is_correct to the student during the attempt!
                $formattedOptions[] = [
                    'id' => $option->id,
                    'text' => $option->getTranslations('text'),
                ];
            }

            $formatted[] = [
                'id' => $question->id,
                'type' => $question->type,
                'text' => $question->getTranslations('text'),
                'points' => $question->points,
                'position' => $question->position,
                'options' => $formattedOptions,
            ];
        }

        return $formatted;
    }
}
