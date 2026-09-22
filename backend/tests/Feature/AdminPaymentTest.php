<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function setupAdminPaymentTestData(): array
{
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
        'slug' => 'admin-test-course',
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

    $payment = Payment::create([
        'user_id' => $student->getKey(),
        'course_id' => $course->getKey(),
        'term_id' => $term->getKey(),
        'method' => 'vodafone_cash',
        'list_price_cents' => 30000,
        'discount_cents' => 0,
        'amount_due_cents' => 30000,
        'sender_identifier' => '01012345678',
        'proof_path' => 'proofs/test.jpg',
        'proof_hash' => hash('sha256', 'admin_test'),
        'status' => 'pending',
    ]);

    return compact('student', 'admin', 'course', 'term', 'payment');
}

it('allows an admin to view the payment review queue', function (): void {
    $data = setupAdminPaymentTestData();

    $response = $this->actingAs($data['admin'], 'sanctum')
        ->getJson('/api/v1/admin/payments');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'pending')
        ->assertJsonStructure(['counts']);
});

it('allows an admin to approve a payment and creates enrollment', function (): void {
    $data = setupAdminPaymentTestData();

    $response = $this->actingAs($data['admin'], 'sanctum')
        ->postJson('/api/v1/admin/payments/'.$data['payment']->getKey().'/approve');

    $response->assertOk()
        ->assertJsonPath('data.source', 'payment')
        ->assertJsonPath('data.status', 'active');

    // Verify payment was updated
    $payment = Payment::find($data['payment']->getKey());
    expect($payment->getAttribute('status'))->toBe('approved');
    expect($payment->getAttribute('teacher_share_percent'))->toBe(70);
    expect($payment->getAttribute('teacher_share_cents'))->toBe(21000); // 70% of 30000
    expect($payment->getAttribute('platform_share_cents'))->toBe(9000); // 30% of 30000

    // Verify enrollment was created
    $this->assertDatabaseHas('enrollments', [
        'user_id' => $data['student']->getKey(),
        'course_id' => $data['course']->getKey(),
        'source' => 'payment',
        'status' => 'active',
    ]);
});

it('is idempotent when approving an already approved payment', function (): void {
    $data = setupAdminPaymentTestData();

    // Approve first time
    $this->actingAs($data['admin'], 'sanctum')
        ->postJson('/api/v1/admin/payments/'.$data['payment']->getKey().'/approve')
        ->assertOk();

    // Approve second time — should return existing enrollment, not error
    $response = $this->actingAs($data['admin'], 'sanctum')
        ->postJson('/api/v1/admin/payments/'.$data['payment']->getKey().'/approve');

    $response->assertOk();

    // Only one enrollment should exist
    $enrollmentCount = Enrollment::query()
        ->where('user_id', $data['student']->getKey())
        ->where('course_id', $data['course']->getKey())
        ->count();

    expect($enrollmentCount)->toBe(1);
});

it('allows an admin to reject a payment with a reason', function (): void {
    $data = setupAdminPaymentTestData();

    $response = $this->actingAs($data['admin'], 'sanctum')
        ->postJson('/api/v1/admin/payments/'.$data['payment']->getKey().'/reject', [
            'rejection_reason' => 'صورة الإيصال غير واضحة',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'rejected')
        ->assertJsonPath('data.rejection_reason', 'صورة الإيصال غير واضحة');
});

it('requires a reason when rejecting a payment', function (): void {
    $data = setupAdminPaymentTestData();

    $this->actingAs($data['admin'], 'sanctum')
        ->postJson('/api/v1/admin/payments/'.$data['payment']->getKey().'/reject', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['rejection_reason']);
});

it('denies a student from accessing the admin payment queue', function (): void {
    $data = setupAdminPaymentTestData();

    $this->actingAs($data['student'], 'sanctum')
        ->getJson('/api/v1/admin/payments')
        ->assertForbidden();
});

it('denies a student from approving a payment', function (): void {
    $data = setupAdminPaymentTestData();

    $this->actingAs($data['student'], 'sanctum')
        ->postJson('/api/v1/admin/payments/'.$data['payment']->getKey().'/approve')
        ->assertForbidden();
});

it('detects duplicate proof hashes', function (): void {
    $data = setupAdminPaymentTestData();

    // Create a second payment with the same proof hash
    $otherStudent = User::factory()->create();
    $otherStudent->assignRole('student');

    Payment::create([
        'user_id' => $otherStudent->getKey(),
        'course_id' => $data['course']->getKey(),
        'term_id' => $data['term']->getKey(),
        'method' => 'vodafone_cash',
        'list_price_cents' => 30000,
        'discount_cents' => 0,
        'amount_due_cents' => 30000,
        'sender_identifier' => '01099999999',
        'proof_path' => 'proofs/dup.jpg',
        'proof_hash' => hash('sha256', 'admin_test'), // Same hash
        'status' => 'pending',
    ]);

    $response = $this->actingAs($data['admin'], 'sanctum')
        ->getJson('/api/v1/admin/payments');

    $response->assertOk();

    // Both payments should have has_duplicate_proof = true
    $payments = $response->json('data');
    foreach ($payments as $p) {
        expect($p['has_duplicate_proof'])->toBeTrue();
    }
});
