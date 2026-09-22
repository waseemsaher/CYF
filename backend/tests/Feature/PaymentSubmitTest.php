<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function setupPaymentTestData(): array
{
    // Create roles and permissions
    Permission::findOrCreate('payments.review', 'web');
    Permission::findOrCreate('courses.manage', 'web');
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::findOrCreate('superadmin', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo('payments.review');
    Role::findOrCreate('student', 'web');

    $student = User::factory()->create(['email_verified_at' => now()]);
    $student->assignRole('student');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $course = Course::create([
        'slug' => 'test-course',
        'title' => ['ar' => 'دورة اختبارية', 'en' => 'Test Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Description'],
        'price_cents' => 30000,
        'status' => 'published',
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل اختبار', 'en' => 'Test Term'],
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addMonths(3),
        'is_current' => true,
        'sort_order' => 1,
    ]);

    Setting::setValue('revenue', 'default_teacher_share_percent', 70);
    Setting::setValue('enrollment', 'grace_days', 0);
    Setting::setValue('uploads', 'proof_max_size_kb', 5120);

    return compact('student', 'admin', 'course', 'term');
}

it('allows a student to submit a payment', function (): void {
    Storage::fake('local');
    $data = setupPaymentTestData();

    $proof = UploadedFile::fake()->image('receipt.jpg', 800, 600);

    $response = $this->actingAs($data['student'], 'sanctum')
        ->postJson('/api/v1/payments', [
            'course_id' => $data['course']->getKey(),
            'term_id' => $data['term']->getKey(),
            'method' => 'vodafone_cash',
            'sender_identifier' => '01012345678',
            'proof' => $proof,
            'student_note' => 'test note',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.amount_due_cents', 30000);

    $this->assertDatabaseHas('payments', [
        'user_id' => $data['student']->getKey(),
        'course_id' => $data['course']->getKey(),
        'status' => 'pending',
    ]);
});

it('rejects a second pending payment for the same course/term', function (): void {
    Storage::fake('local');
    $data = setupPaymentTestData();

    // Create first pending payment
    Payment::create([
        'user_id' => $data['student']->getKey(),
        'course_id' => $data['course']->getKey(),
        'term_id' => $data['term']->getKey(),
        'method' => 'vodafone_cash',
        'list_price_cents' => 30000,
        'discount_cents' => 0,
        'amount_due_cents' => 30000,
        'sender_identifier' => '01012345678',
        'proof_path' => 'proofs/test.jpg',
        'proof_hash' => hash('sha256', 'test'),
        'status' => 'pending',
    ]);

    $proof = UploadedFile::fake()->image('receipt2.jpg', 800, 600);

    $response = $this->actingAs($data['student'], 'sanctum')
        ->postJson('/api/v1/payments', [
            'course_id' => $data['course']->getKey(),
            'term_id' => $data['term']->getKey(),
            'method' => 'vodafone_cash',
            'sender_identifier' => '01012345678',
            'proof' => $proof,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['course_id']);
});

it('creates instant enrollment for free courses', function (): void {
    Storage::fake('local');
    $data = setupPaymentTestData();

    // Make course free
    $data['course']->update(['price_cents' => 0]);

    $proof = UploadedFile::fake()->image('receipt.jpg', 800, 600);

    $response = $this->actingAs($data['student'], 'sanctum')
        ->postJson('/api/v1/payments', [
            'course_id' => $data['course']->getKey(),
            'term_id' => $data['term']->getKey(),
            'method' => 'vodafone_cash',
            'sender_identifier' => '01012345678',
            'proof' => $proof,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.source', 'free')
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('enrollments', [
        'user_id' => $data['student']->getKey(),
        'course_id' => $data['course']->getKey(),
        'source' => 'free',
    ]);
});

it('lists only the students own payments', function (): void {
    $data = setupPaymentTestData();
    $otherStudent = User::factory()->create();
    $otherStudent->assignRole('student');

    Payment::create([
        'user_id' => $data['student']->getKey(),
        'course_id' => $data['course']->getKey(),
        'term_id' => $data['term']->getKey(),
        'method' => 'vodafone_cash',
        'list_price_cents' => 30000,
        'discount_cents' => 0,
        'amount_due_cents' => 30000,
        'sender_identifier' => '01012345678',
        'proof_path' => 'proofs/test.jpg',
        'proof_hash' => hash('sha256', 'test1'),
        'status' => 'pending',
    ]);

    Payment::create([
        'user_id' => $otherStudent->getKey(),
        'course_id' => $data['course']->getKey(),
        'term_id' => $data['term']->getKey(),
        'method' => 'vodafone_cash',
        'list_price_cents' => 30000,
        'discount_cents' => 0,
        'amount_due_cents' => 30000,
        'sender_identifier' => '01099999999',
        'proof_path' => 'proofs/other.jpg',
        'proof_hash' => hash('sha256', 'test2'),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($data['student'], 'sanctum')
        ->getJson('/api/v1/payments');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('prevents a student from viewing another students payment', function (): void {
    $data = setupPaymentTestData();
    $otherStudent = User::factory()->create();
    $otherStudent->assignRole('student');

    $payment = Payment::create([
        'user_id' => $otherStudent->getKey(),
        'course_id' => $data['course']->getKey(),
        'term_id' => $data['term']->getKey(),
        'method' => 'vodafone_cash',
        'list_price_cents' => 30000,
        'discount_cents' => 0,
        'amount_due_cents' => 30000,
        'sender_identifier' => '01099999999',
        'proof_path' => 'proofs/other.jpg',
        'proof_hash' => hash('sha256', 'other'),
        'status' => 'pending',
    ]);

    $this->actingAs($data['student'], 'sanctum')
        ->getJson('/api/v1/payments/'.$payment->getKey())
        ->assertForbidden();
});

it('allows a student to cancel their own pending payment', function (): void {
    $data = setupPaymentTestData();

    $payment = Payment::create([
        'user_id' => $data['student']->getKey(),
        'course_id' => $data['course']->getKey(),
        'term_id' => $data['term']->getKey(),
        'method' => 'vodafone_cash',
        'list_price_cents' => 30000,
        'discount_cents' => 0,
        'amount_due_cents' => 30000,
        'sender_identifier' => '01012345678',
        'proof_path' => 'proofs/test.jpg',
        'proof_hash' => hash('sha256', 'cancel_test'),
        'status' => 'pending',
    ]);

    $this->actingAs($data['student'], 'sanctum')
        ->postJson('/api/v1/payments/'.$payment->getKey().'/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});
