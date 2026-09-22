<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\AssignTeacherToCourse;
use App\Domain\Identity\Actions\CalculateTeacherBalance;
use App\Domain\Identity\Actions\RecordTeacherPayout;
use App\Models\AttemptAnswer;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::create(['name' => 'superadmin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'teacher']);
    Role::create(['name' => 'student']);
});

it('accurately calculates teacher balance as earned shares minus payouts', function (): void {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $course = Course::create([
        'slug' => 'algorithms',
        'title' => ['ar' => 'خوارزميات', 'en' => 'Algorithms'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 10000,
    ]);

    app(AssignTeacherToCourse::class)->handle($course, $teacher, 70);

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $student1 = User::factory()->create();
    $student2 = User::factory()->create();

    // Payment 1: teacher share 7000 cents
    Payment::create([
        'user_id' => $student1->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'method' => 'vodafone_cash',
        'list_price_cents' => 10000,
        'discount_cents' => 0,
        'amount_due_cents' => 10000,
        'sender_identifier' => '01011112222',
        'proof_path' => 'proofs/1.jpg',
        'proof_hash' => 'hash1',
        'status' => 'approved',
        'teacher_share_percent' => 70,
        'teacher_share_cents' => 7000,
        'platform_share_cents' => 3000,
    ]);

    // Payment 2: teacher share 7000 cents
    Payment::create([
        'user_id' => $student2->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'method' => 'instapay',
        'list_price_cents' => 10000,
        'discount_cents' => 0,
        'amount_due_cents' => 10000,
        'sender_identifier' => 'user@instapay',
        'proof_path' => 'proofs/2.jpg',
        'proof_hash' => 'hash2',
        'status' => 'approved',
        'teacher_share_percent' => 70,
        'teacher_share_cents' => 7000,
        'platform_share_cents' => 3000,
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Record payout of 5000 cents
    app(RecordTeacherPayout::class)->handle($teacher, 5000, $admin, 'دفعة أولى');

    $balance = app(CalculateTeacherBalance::class)->handle($teacher);

    expect($balance['earned_cents'])->toBe(14000)
        ->and($balance['paid_out_cents'])->toBe(5000)
        ->and($balance['balance_cents'])->toBe(9000);
});

it('allows teacher to view dashboard overview and course student roster', function (): void {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $course = Course::create([
        'slug' => 'algorithms',
        'title' => ['ar' => 'خوارزميات', 'en' => 'Algorithms'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 10000,
    ]);

    app(AssignTeacherToCourse::class)->handle($course, $teacher, 70);

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
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

    // Teacher dashboard
    $dashResponse = $this->actingAs($teacher)->getJson('/api/v1/teacher/dashboard');
    $dashResponse->assertOk()
        ->assertJsonPath('data.courses.0.slug', 'algorithms')
        ->assertJsonPath('data.courses.0.active_students_count', 1);

    // Course students
    $studentsResponse = $this->actingAs($teacher)->getJson("/api/v1/teacher/courses/{$course->id}/students");
    $studentsResponse->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.user.id', $student->id);
});

it('provides quiz analytics including average score and question accuracy', function (): void {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $course = Course::create([
        'slug' => 'algorithms',
        'title' => ['ar' => 'خوارزميات', 'en' => 'Algorithms'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 10000,
    ]);

    app(AssignTeacherToCourse::class)->handle($course, $teacher, 70);

    $quiz = Quiz::create([
        'course_id' => $course->id,
        'kind' => 'quiz',
        'title' => ['ar' => 'كويز 1', 'en' => 'Quiz 1'],
    ]);

    $q1 = Question::create([
        'quiz_id' => $quiz->id,
        'type' => 'mcq',
        'text' => ['ar' => 'سؤال 1', 'en' => 'Q1'],
        'points' => 10,
    ]);
    $optCorrect = QuestionOption::create(['question_id' => $q1->id, 'text' => ['ar' => 'صح', 'en' => 'True'], 'is_correct' => true]);

    $student = User::factory()->create();

    $attempt = QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
        'started_at' => now()->subMinutes(10),
        'submitted_at' => now(),
        'score' => 10,
        'max_score' => 10,
        'status' => 'submitted',
    ]);

    AttemptAnswer::create([
        'attempt_id' => $attempt->id,
        'question_id' => $q1->id,
        'selected_option_ids' => [$optCorrect->id],
        'is_correct' => true,
        'points_awarded' => 10,
    ]);

    $response = $this->actingAs($teacher)->getJson("/api/v1/teacher/quizzes/{$quiz->id}/analytics");

    $response->assertOk()
        ->assertJsonPath('data.stats.total_attempts', 1)
        ->assertJsonPath('data.stats.average_score', 10)
        ->assertJsonPath('data.questions.0.accuracy_rate', 100);
});
