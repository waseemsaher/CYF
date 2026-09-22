<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows enrolled student to start quiz attempt without leaking correct options', function (): void {
    $course = Course::create([
        'slug' => 'physics',
        'title' => ['ar' => 'فيزياء', 'en' => 'Physics'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 12000,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $quiz = Quiz::create([
        'course_id' => $course->id,
        'kind' => 'quiz',
        'title' => ['ar' => 'اختبار 1', 'en' => 'Quiz 1'],
        'duration_minutes' => 30,
        'max_attempts' => 2,
        'results_visibility' => 'immediate',
    ]);

    $question = Question::create([
        'quiz_id' => $quiz->id,
        'type' => 'mcq',
        'text' => ['ar' => 'ما هي وحدة القوة؟', 'en' => 'Unit of force?'],
        'points' => 5,
        'position' => 1,
    ]);

    $opt1 = QuestionOption::create(['question_id' => $question->id, 'text' => ['ar' => 'نيوتن', 'en' => 'Newton'], 'is_correct' => true]);
    $opt2 = QuestionOption::create(['question_id' => $question->id, 'text' => ['ar' => 'جول', 'en' => 'Joule'], 'is_correct' => false]);

    $student = User::factory()->create();

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonths(2),
    ]);

    $response = $this->actingAs($student)->postJson("/api/v1/quizzes/{$quiz->id}/start");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'attempt' => ['id', 'status', 'started_at', 'duration_minutes'],
                'quiz' => ['id', 'title'],
                'questions' => [
                    '*' => [
                        'id',
                        'type',
                        'text',
                        'points',
                        'options' => [
                            '*' => ['id', 'text'],
                        ],
                    ],
                ],
            ],
        ]);

    // Ensure is_correct is NOT leaked in the questions payload
    $questions = $response->json('data.questions');
    foreach ($questions[0]['options'] as $option) {
        expect(array_key_exists('is_correct', $option))->toBeFalse();
    }
});

it('accurately grades submitted quiz attempt and awards points', function (): void {
    $course = Course::create([
        'slug' => 'physics',
        'title' => ['ar' => 'فيزياء', 'en' => 'Physics'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 12000,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $quiz = Quiz::create([
        'course_id' => $course->id,
        'kind' => 'quiz',
        'title' => ['ar' => 'اختبار 1', 'en' => 'Quiz 1'],
        'max_attempts' => 1,
        'results_visibility' => 'immediate',
    ]);

    $q1 = Question::create([
        'quiz_id' => $quiz->id,
        'type' => 'mcq',
        'text' => ['ar' => 'سؤال 1', 'en' => 'Q1'],
        'points' => 10,
        'position' => 1,
    ]);
    $optCorrect1 = QuestionOption::create(['question_id' => $q1->id, 'text' => ['ar' => 'صح', 'en' => 'True'], 'is_correct' => true]);
    $optWrong1 = QuestionOption::create(['question_id' => $q1->id, 'text' => ['ar' => 'خطأ', 'en' => 'False'], 'is_correct' => false]);

    $q2 = Question::create([
        'quiz_id' => $quiz->id,
        'type' => 'mcq',
        'text' => ['ar' => 'سؤال 2', 'en' => 'Q2'],
        'points' => 5,
        'position' => 2,
    ]);
    $optCorrect2 = QuestionOption::create(['question_id' => $q2->id, 'text' => ['ar' => 'صح', 'en' => 'True'], 'is_correct' => true]);
    $optWrong2 = QuestionOption::create(['question_id' => $q2->id, 'text' => ['ar' => 'خطأ', 'en' => 'False'], 'is_correct' => false]);

    $student = User::factory()->create();

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonths(2),
    ]);

    $startResponse = $this->actingAs($student)->postJson("/api/v1/quizzes/{$quiz->id}/start");
    $attemptId = $startResponse->json('data.attempt.id');

    // Submit: answer Q1 correctly ($optCorrect1) and Q2 incorrectly ($optWrong2)
    $submitResponse = $this->actingAs($student)->postJson("/api/v1/quizzes/{$quiz->id}/attempts/{$attemptId}/submit", [
        'answers' => [
            $q1->id => [$optCorrect1->id],
            $q2->id => [$optWrong2->id],
        ],
    ]);

    $submitResponse->assertOk()
        ->assertJsonPath('data.score', 10)
        ->assertJsonPath('data.max_score', 15)
        ->assertJsonPath('data.status', 'submitted');
});

it('enforces maximum attempts limit', function (): void {
    $course = Course::create([
        'slug' => 'physics',
        'title' => ['ar' => 'فيزياء', 'en' => 'Physics'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 12000,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $quiz = Quiz::create([
        'course_id' => $course->id,
        'kind' => 'quiz',
        'title' => ['ar' => 'اختبار', 'en' => 'Quiz'],
        'max_attempts' => 1,
        'results_visibility' => 'immediate',
    ]);

    $student = User::factory()->create();

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonths(2),
    ]);

    // 1st attempt
    $start = $this->actingAs($student)->postJson("/api/v1/quizzes/{$quiz->id}/start");
    $attemptId = $start->json('data.attempt.id');
    $this->actingAs($student)->postJson("/api/v1/quizzes/{$quiz->id}/attempts/{$attemptId}/submit", ['answers' => []])->assertOk();

    // 2nd attempt should fail (422)
    $secondAttempt = $this->actingAs($student)->postJson("/api/v1/quizzes/{$quiz->id}/start");
    $secondAttempt->assertStatus(422);
});
