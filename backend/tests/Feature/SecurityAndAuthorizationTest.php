<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Payment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
});

test('api responses include standard security headers', function (): void {
    $response = $this->getJson('/api/v1/courses');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-XSS-Protection', '1; mode=block')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
        ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none')
        ->assertHeader('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: blob:; media-src 'self'; connect-src 'self' http://localhost:8000 https:; frame-ancestors 'self'; form-action 'self'");
});

test('https requests return Strict-Transport-Security header', function (): void {
    $response = $this->get('https://localhost/api/v1/courses');

    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

test('cors preflight and headers handle allowed origins', function (): void {
    $response = $this->withHeaders([
        'Origin' => 'http://localhost:5173',
    ])->getJson('/api/v1/courses');

    $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->assertHeader('Access-Control-Allow-Credentials', 'true');
});

test('cors rejects untrusted origins', function (): void {
    $response = $this->withHeaders([
        'Origin' => 'https://malicious-site.attacker.com',
    ])->getJson('/api/v1/courses');

    $response->assertHeaderMissing('Access-Control-Allow-Origin');
});

test('unauthenticated users cannot access protected student or admin routes', function (): void {
    $this->getJson('/api/v1/me')->assertUnauthorized();
    $this->getJson('/api/v1/payments')->assertUnauthorized();
    $this->getJson('/api/v1/admin/overview')->assertUnauthorized();
    $this->getJson('/api/v1/teacher/dashboard')->assertUnauthorized();
});

test('students cannot access admin or teacher endpoints', function (): void {
    $student = User::factory()->create();
    $student->assignRole('student');

    $this->actingAs($student)
        ->getJson('/api/v1/admin/overview')
        ->assertForbidden();

    $this->actingAs($student)
        ->getJson('/api/v1/admin/settings')
        ->assertForbidden();

    $this->actingAs($student)
        ->getJson('/api/v1/admin/payments')
        ->assertForbidden();

    $this->actingAs($student)
        ->getJson('/api/v1/teacher/dashboard')
        ->assertForbidden();
});

test('students cannot view or cancel another students payment', function (): void {
    $studentA = User::factory()->create();
    $studentA->assignRole('student');

    $studentB = User::factory()->create();
    $studentB->assignRole('student');

    $year = AcademicYear::create(['name' => ['ar' => 'سنة 1', 'en' => 'Year 1']]);
    $dept = Department::create(['code' => 'CS', 'name' => ['ar' => 'علوم', 'en' => 'CS']]);
    $term = Term::create(['name' => ['ar' => 'ترم 1', 'en' => 'Term 1'], 'starts_at' => now(), 'ends_at' => now()->addMonths(3)]);

    $course = Course::create([
        'academic_year_id' => $year->id,
        'department_id' => $dept->id,
        'term_id' => $term->id,
        'title' => ['ar' => 'دورة', 'en' => 'Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Description'],
        'slug' => 'sec-course-1',
        'price_cents' => 20000,
        'teacher_share_percentage' => 70,
        'is_published' => true,
    ]);

    $paymentB = Payment::create([
        'user_id' => $studentB->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'method' => 'vodafone_cash',
        'sender_identifier' => '01012345678',
        'list_price_cents' => 20000,
        'amount_due_cents' => 20000,
        'amount_cents' => 20000,
        'status' => 'pending',
        'proof_path' => 'proofs/test.jpg',
        'proof_hash' => 'hash123',
    ]);

    // Student A attempts to view Student B's payment
    $this->actingAs($studentA)
        ->getJson("/api/v1/payments/{$paymentB->id}")
        ->assertForbidden();

    // Student A attempts to cancel Student B's payment
    $this->actingAs($studentA)
        ->postJson("/api/v1/payments/{$paymentB->id}/cancel")
        ->assertForbidden();
});

test('students cannot view another students quiz attempt', function (): void {
    $studentA = User::factory()->create();
    $studentA->assignRole('student');

    $studentB = User::factory()->create();
    $studentB->assignRole('student');

    $year = AcademicYear::create(['name' => ['ar' => 'سنة 1', 'en' => 'Year 1']]);
    $dept = Department::create(['code' => 'IS', 'name' => ['ar' => 'نظم', 'en' => 'IS']]);
    $term = Term::create(['name' => ['ar' => 'ترم 1', 'en' => 'Term 1'], 'starts_at' => now(), 'ends_at' => now()->addMonths(3)]);

    $course = Course::create([
        'academic_year_id' => $year->id,
        'department_id' => $dept->id,
        'term_id' => $term->id,
        'title' => ['ar' => 'دورة اختبار', 'en' => 'Quiz Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Description'],
        'slug' => 'quiz-sec-course',
        'price_cents' => 10000,
        'is_published' => true,
    ]);

    $quiz = Quiz::create([
        'course_id' => $course->id,
        'title' => ['ar' => 'اختبار أمني', 'en' => 'Security Quiz'],
        'duration_minutes' => 30,
        'max_attempts' => 1,
        'is_published' => true,
    ]);

    $attemptB = QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'user_id' => $studentB->id,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $this->actingAs($studentA)
        ->getJson("/api/v1/quizzes/{$quiz->id}/attempts/{$attemptB->id}")
        ->assertForbidden();
});

test('teachers cannot view student rosters or quiz analytics for courses they do not teach', function (): void {
    $teacherA = User::factory()->create();
    $teacherA->assignRole('teacher');

    $year = AcademicYear::create(['name' => ['ar' => 'سنة 1', 'en' => 'Year 1']]);
    $dept = Department::create(['code' => 'IT', 'name' => ['ar' => 'تكنولوجيا', 'en' => 'IT']]);
    $term = Term::create(['name' => ['ar' => 'ترم 1', 'en' => 'Term 1'], 'starts_at' => now(), 'ends_at' => now()->addMonths(3)]);

    $unassignedCourse = Course::create([
        'academic_year_id' => $year->id,
        'department_id' => $dept->id,
        'term_id' => $term->id,
        'title' => ['ar' => 'مقرر غير مسند', 'en' => 'Unassigned Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Description'],
        'slug' => 'unassigned-course',
        'price_cents' => 15000,
        'is_published' => true,
    ]);

    $quiz = Quiz::create([
        'course_id' => $unassignedCourse->id,
        'title' => ['ar' => 'اختبار تحليلي', 'en' => 'Analytics Quiz'],
        'duration_minutes' => 20,
        'is_published' => true,
    ]);

    $this->actingAs($teacherA)
        ->getJson("/api/v1/teacher/courses/{$unassignedCourse->id}/students")
        ->assertForbidden();

    $this->actingAs($teacherA)
        ->getJson("/api/v1/teacher/quizzes/{$quiz->id}/analytics")
        ->assertForbidden();
});

test('login endpoint rate limits excessive attempts after 5 requests', function (): void {
    RateLimiter::clear('login');

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/login', [
            'email' => 'rl-user@example.com',
            'password' => 'wrong-pass',
        ]);
    }

    $response = $this->postJson('/api/v1/login', [
        'email' => 'rl-user@example.com',
        'password' => 'wrong-pass',
    ]);

    $response->assertStatus(429);
});

test('registration endpoint rate limits excessive attempts per IP after 5 requests', function (): void {
    RateLimiter::clear('register');

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/register', [
            'name' => "User {$i}",
            'email' => "register{$i}@example.com",
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'branch' => 'cairo',
            'academic_year' => 'first',
            'department' => 'general',
        ]);
    }

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Excess User',
        'email' => 'excess@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'branch' => 'cairo',
        'academic_year' => 'first',
        'department' => 'general',
    ]);

    $response->assertStatus(429);
});

test('forgot password endpoint rate limits after 3 attempts', function (): void {
    RateLimiter::clear('password-reset');

    for ($i = 0; $i < 3; $i++) {
        $this->postJson('/api/v1/forgot-password', [
            'email' => 'student-reset@example.com',
        ]);
    }

    $response = $this->postJson('/api/v1/forgot-password', [
        'email' => 'student-reset@example.com',
    ]);

    $response->assertStatus(429);
});

test('assistant admin with only payments.review cannot manage courses or edit course prices', function (): void {
    Permission::findOrCreate('payments.review', 'web');
    Permission::findOrCreate('courses.manage', 'web');

    $assistant = User::factory()->create();
    $assistant->givePermissionTo('payments.review');

    $course = Course::create([
        'slug' => 'perm-test-course',
        'title' => ['ar' => 'دورة', 'en' => 'Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 20000,
        'status' => 'published',
    ]);

    // Assistant admin attempts to edit course price
    $response = $this->actingAs($assistant)
        ->putJson("/api/v1/admin/courses/{$course->id}", [
            'title' => ['ar' => 'دورة معدلة', 'en' => 'Updated Course'],
            'price_cents' => 99999,
        ]);

    $response->assertForbidden();
});

test('assistant admin without admin role cannot grant enrollments', function (): void {
    Permission::findOrCreate('payments.review', 'web');

    $assistant = User::factory()->create();
    $assistant->givePermissionTo('payments.review');

    $student = User::factory()->create();
    $course = Course::create([
        'slug' => 'perm-enroll-course',
        'title' => ['ar' => 'دورة', 'en' => 'Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
        'status' => 'published',
    ]);
    $term = Term::create([
        'name' => ['ar' => 'ترم', 'en' => 'Term'],
        'starts_at' => now(),
        'ends_at' => now()->addMonths(3),
    ]);

    $response = $this->actingAs($assistant)
        ->postJson('/api/v1/admin/enrollments/grant', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'term_id' => $term->id,
        ]);

    $response->assertForbidden();
});

test('payment submission endpoint rate limits excessive requests after 10 requests per user', function (): void {
    RateLimiter::clear('payment-submit');

    $student = User::factory()->create();
    $student->assignRole('student');

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($student)->postJson('/api/v1/payments', []);
    }

    $response = $this->actingAs($student)->postJson('/api/v1/payments', []);
    $response->assertStatus(429);
});

test('telegram webhook endpoint rate limits excessive requests per IP', function (): void {
    RateLimiter::clear('telegram-webhook');

    // Simulate 300 requests
    for ($i = 0; $i < 300; $i++) {
        $this->postJson('/api/v1/telegram/webhook', []);
    }

    $response = $this->postJson('/api/v1/telegram/webhook', []);
    $response->assertStatus(429);
});
