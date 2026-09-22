<?php

declare(strict_types=1);

namespace App\Domain\Learning\Actions;

use App\Models\QuestionOption;
use App\Models\QuizAttempt;
use App\Models\User;

class GetQuizAttemptResult
{
    /**
     * @return array<string, mixed>
     */
    public function handle(QuizAttempt $attempt, User $viewer): array
    {
        $quiz = $attempt->quiz;
        $isStaff = $viewer->hasRole(['superadmin', 'admin']) || $viewer->can('courses.manage');
        $visibility = $quiz->results_visibility;

        $canViewScore = $isStaff || $visibility === 'immediate' || ($visibility === 'after_close' && ($quiz->available_until === null || $quiz->available_until->isPast()));
        $canViewAnswers = $isStaff || $visibility === 'immediate' || ($visibility === 'after_close' && $quiz->available_until !== null && $quiz->available_until->isPast());

        $result = [
            'id' => $attempt->id,
            'quiz_id' => $quiz->id,
            'status' => $attempt->status,
            'started_at' => $attempt->started_at->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'visibility' => $visibility,
            'score' => $canViewScore ? $attempt->score : null,
            'max_score' => $canViewScore ? $attempt->max_score : null,
            'percentage' => ($canViewScore && $attempt->max_score > 0)
                ? round(($attempt->score / $attempt->max_score) * 100, 1)
                : null,
        ];

        if ($canViewAnswers) {
            $questions = $quiz->questions()->with(['options', 'answers' => function ($q) use ($attempt): void {
                $q->where('attempt_id', $attempt->id);
            }])->get();

            $breakdown = [];
            foreach ($questions as $question) {
                $answer = $question->answers->first();

                $breakdown[] = [
                    'question_id' => $question->id,
                    'text' => $question->getTranslations('text'),
                    'explanation' => $question->getTranslations('explanation'),
                    'points' => $question->points,
                    'points_awarded' => $answer !== null ? $answer->points_awarded : 0,
                    'is_correct' => $answer !== null ? $answer->is_correct : false,
                    'selected_option_ids' => $answer !== null ? $answer->selected_option_ids : [],
                    'options' => $question->options->map(fn (QuestionOption $opt) => [
                        'id' => $opt->id,
                        'text' => $opt->getTranslations('text'),
                        'is_correct' => $opt->is_correct,
                    ])->all(),
                ];
            }

            $result['breakdown'] = $breakdown;
        }

        return $result;
    }
}
