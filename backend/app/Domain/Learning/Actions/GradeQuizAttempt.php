<?php

declare(strict_types=1);

namespace App\Domain\Learning\Actions;

use App\Models\AttemptAnswer;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GradeQuizAttempt
{
    /**
     * @param  array<int|string, list<int>|int>  $answers  Map of question_id => array of selected option IDs
     */
    public function handle(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'attempt' => ['هذه المحاولة مكتملة بالفعل.'],
            ]);
        }

        $quiz = $attempt->quiz;

        // Check duration expiry (+1 minute grace period for network latency)
        if ($quiz->duration_minutes !== null) {
            $deadline = $attempt->started_at->addMinutes($quiz->duration_minutes)->addMinute();
            if (now()->isAfter($deadline)) {
                $attempt->update([
                    'status' => 'expired',
                    'submitted_at' => now(),
                ]);

                throw ValidationException::withMessages([
                    'attempt' => ['انتهى وقت الاختبار المسموح به.'],
                ]);
            }
        }

        return DB::transaction(function () use ($attempt, $quiz, $answers): QuizAttempt {
            $totalScore = 0;
            $questions = $quiz->questions()->with('options')->get();

            foreach ($questions as $question) {
                $correctOptionIds = $question->options
                    ->where('is_correct', true)
                    ->pluck('id')
                    ->all();
                sort($correctOptionIds);

                $rawSelected = $answers[$question->id] ?? $answers[(string) $question->id] ?? [];
                $selectedOptionIds = is_array($rawSelected) ? array_map('intval', $rawSelected) : [intval($rawSelected)];
                sort($selectedOptionIds);

                $isCorrect = ! empty($correctOptionIds) && ($correctOptionIds === $selectedOptionIds);
                $pointsAwarded = $isCorrect ? $question->points : 0;
                $totalScore += $pointsAwarded;

                AttemptAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_option_ids' => $selectedOptionIds,
                    'is_correct' => $isCorrect,
                    'points_awarded' => $pointsAwarded,
                ]);
            }

            $attempt->update([
                'score' => $totalScore,
                'submitted_at' => now(),
                'status' => 'submitted',
            ]);

            return $attempt->fresh(['answers']);
        });
    }
}
