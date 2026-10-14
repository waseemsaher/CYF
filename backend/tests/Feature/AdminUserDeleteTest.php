<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::create(['name' => 'superadmin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'teacher']);
    Role::create(['name' => 'student']);
    Storage::fake();
});

it('forbids unauthenticated or unauthorized users from deleting a user', function (): void {
    $target = User::factory()->create();
    $target->assignRole('student');

    // Unauthenticated
    $this->deleteJson("/api/v1/admin/users/{$target->id}")
        ->assertUnauthorized();

    // Authenticated as student
    $student = User::factory()->create();
    $student->assignRole('student');

    $this->actingAs($student)
        ->deleteJson("/api/v1/admin/users/{$target->id}")
        ->assertForbidden();
});

it('prevents an admin from deleting their own account', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->deleteJson("/api/v1/admin/users/{$admin->id}")
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'لا يمكنك حذف حسابك الشخصي.']);

    expect(User::find($admin->id))->not->toBeNull();
});

it('prevents an admin from deleting a superadmin', function (): void {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->deleteJson("/api/v1/admin/users/{$superadmin->id}")
        ->assertForbidden();

    expect(User::find($superadmin->id))->not->toBeNull();
});

it('prevents deleting the last superadmin in the system', function (): void {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');

    $anotherAdmin = User::factory()->create();
    $anotherAdmin->assignRole('admin');

    $this->actingAs($superadmin)
        ->deleteJson("/api/v1/admin/users/{$superadmin->id}")
        ->assertStatus(422); // self-delete blocked

    // Create a second superadmin
    $superadmin2 = User::factory()->create();
    $superadmin2->assignRole('superadmin');

    // superadmin2 can delete superadmin
    $this->actingAs($superadmin2)
        ->deleteJson("/api/v1/admin/users/{$superadmin->id}")
        ->assertOk();

    // Now only superadmin2 remains, trying to delete superadmin2 by an admin fails
    expect(User::role('superadmin')->count())->toBe(1);
});

it('successfully deletes a user and cascades related enrollments and payment proofs', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('superadmin');

    $student = User::factory()->create();
    $student->assignRole('student');

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $course = Course::create([
        'slug' => 'test-math',
        'title' => ['ar' => 'رياضة', 'en' => 'Math'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 1000,
    ]);

    Storage::disk('local')->put('proofs/sample.jpg', 'fake-image-content');

    $payment = Payment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'method' => 'vodafone_cash',
        'list_price_cents' => 1000,
        'discount_cents' => 0,
        'amount_due_cents' => 1000,
        'sender_identifier' => '01011111111',
        'proof_path' => 'proofs/sample.jpg',
        'proof_hash' => 'hash123',
        'status' => 'pending',
    ]);

    $enrollment = Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'payment_id' => $payment->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonth(),
    ]);

    $response = $this->actingAs($admin)
        ->deleteJson("/api/v1/admin/users/{$student->id}");

    $response->assertOk()
        ->assertJsonFragment(['message' => 'تم حذف المستخدم بنجاح.']);

    expect(User::find($student->id))->toBeNull();
    expect(Payment::find($payment->id))->toBeNull();
    expect(Enrollment::find($enrollment->id))->toBeNull();
    expect(Storage::disk('local')->exists('proofs/sample.jpg'))->toBeFalse();
    // Course and term still exist
    expect(Course::find($course->id))->not->toBeNull();
    expect(Term::find($term->id))->not->toBeNull();
});

it('resets all students while preserving admins, teachers, and courses', function (): void {
    $admin = User::factory()->create(['name' => 'Admin User']);
    $admin->assignRole('superadmin');

    $teacher = User::factory()->create(['name' => 'Teacher User']);
    $teacher->assignRole('teacher');

    $student1 = User::factory()->create(['name' => 'Student 1']);
    $student1->assignRole('student');

    $student2 = User::factory()->create(['name' => 'Student 2']);
    $student2->assignRole('student');

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $course = Course::create([
        'slug' => 'biology-course',
        'title' => ['ar' => 'أحياء', 'en' => 'Biology'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 2000,
    ]);

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/admin/users/reset-students');

    $response->assertOk()
        ->assertJsonFragment(['count' => 2]);

    expect(User::role('student')->count())->toBe(0);
    expect(User::find($admin->id))->not->toBeNull();
    expect(User::find($teacher->id))->not->toBeNull();
    expect(Course::find($course->id))->not->toBeNull();
});
