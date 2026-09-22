<?php

declare(strict_types=1);

use App\Domain\Settings\Services\ContentBlockService;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
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

it('returns admin overview metrics accurately', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('superadmin');

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $course = Course::create([
        'slug' => 'test-course',
        'title' => ['ar' => 'مادة', 'en' => 'Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 5000,
    ]);

    $student = User::factory()->create();
    $student->assignRole('student');

    // 1 pending payment
    Payment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'method' => 'vodafone_cash',
        'list_price_cents' => 5000,
        'discount_cents' => 0,
        'amount_due_cents' => 5000,
        'sender_identifier' => '01000000000',
        'proof_path' => 'proofs/p.jpg',
        'proof_hash' => 'hash_pending',
        'status' => 'pending',
    ]);

    // 1 approved payment with active enrollment
    Payment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'method' => 'instapay',
        'list_price_cents' => 5000,
        'discount_cents' => 0,
        'amount_due_cents' => 5000,
        'sender_identifier' => 'user@instapay',
        'proof_path' => 'proofs/a.jpg',
        'proof_hash' => 'hash_approved',
        'status' => 'approved',
    ]);

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonths(2),
    ]);

    $response = $this->actingAs($admin)->getJson('/api/v1/admin/overview');

    $response->assertOk()
        ->assertJsonPath('data.pending_payments_count', 1)
        ->assertJsonPath('data.active_enrollments_count', 1)
        ->assertJsonPath('data.term_revenue_cents', 5000)
        ->assertJsonPath('data.total_students_count', 1);
});

it('allows public access to content blocks and supports admin updates with cache invalidation', function (): void {
    $service = app(ContentBlockService::class);
    $service->set('legal.privacy', [
        'ar' => 'سياسة الخصوصية القديمة',
        'en' => 'Old Privacy Policy',
    ], 'legal');

    // Public get
    $publicResponse = $this->getJson('/api/v1/content-blocks/legal.privacy');
    $publicResponse->assertOk()
        ->assertJsonPath('data.content.ar', 'سياسة الخصوصية القديمة');

    $admin = User::factory()->create();
    $admin->assignRole('superadmin');

    // Admin update
    $updateResponse = $this->actingAs($admin)->putJson('/api/v1/admin/content-blocks/legal.privacy', [
        'content' => [
            'ar' => 'سياسة الخصوصية المحدثة',
            'en' => 'Updated Privacy Policy',
        ],
        'group' => 'legal',
    ]);
    $updateResponse->assertOk();

    // Verify cache was invalidated and public endpoint reflects update
    $reFetchResponse = $this->getJson('/api/v1/content-blocks/legal.privacy');
    $reFetchResponse->assertOk()
        ->assertJsonPath('data.content.ar', 'سياسة الخصوصية المحدثة');
});

it('allows admin to search and filter students', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('superadmin');

    $student1 = User::factory()->create([
        'name' => 'محمود أحمد',
        'email' => 'mahmoud@example.com',
        'branch' => 'azhar_boys',
        'academic_year' => '1st',
        'department' => 'CS',
    ]);
    $student1->assignRole('student');

    $student2 = User::factory()->create([
        'name' => 'فاطمة علي',
        'email' => 'fatma@example.com',
        'branch' => 'azhar_girls',
        'academic_year' => '2nd',
        'department' => 'CY',
    ]);
    $student2->assignRole('student');

    // Filter by branch
    $response = $this->actingAs($admin)->getJson('/api/v1/admin/students?branch=azhar_girls');
    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'fatma@example.com');

    // Search by query
    $searchResponse = $this->actingAs($admin)->getJson('/api/v1/admin/students?q=محمود');
    $searchResponse->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'mahmoud@example.com');
});
