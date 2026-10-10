<?php

declare(strict_types=1);

use App\Domain\Payments\Actions\CancelPayment;
use App\Domain\Payments\Actions\SubmitPayment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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

it('rejects submitting payment when student already has an active enrollment', function (): void {
    Storage::fake('local');
    $data = setupPaymentTestData();

    Enrollment::create([
        'user_id' => $data['student']->getKey(),
        'course_id' => $data['course']->getKey(),
        'term_id' => $data['term']->getKey(),
        'source' => 'admin_grant',
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonths(3),
    ]);

    $proof = UploadedFile::fake()->image('receipt.jpg', 800, 600);

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

it('throws DomainException when attempting to cancel non-pending payment in CancelPayment action', function (): void {
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
        'proof_hash' => hash('sha256', 'cancel_race_test'),
        'status' => 'approved',
    ]);

    $action = app(CancelPayment::class);

    expect(fn () => $action->handle($payment, $data['student']))
        ->toThrow(DomainException::class, 'Only pending payments can be cancelled. Current status: approved');
});

it('returns 422 when CancelPayment throws DomainException during cancel request', function (): void {
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
        'proof_hash' => hash('sha256', 'cancel_race_test_2'),
        'status' => 'pending',
    ]);

    $mock = Mockery::mock(CancelPayment::class);
    $mock->shouldReceive('handle')->andThrow(new DomainException('Only pending payments can be cancelled. Current status: approved'));
    $this->app->instance(CancelPayment::class, $mock);

    $this->actingAs($data['student'], 'sanctum')
        ->postJson('/api/v1/payments/'.$payment->getKey().'/cancel')
        ->assertStatus(422)
        ->assertJsonPath('message', 'Only pending payments can be cancelled. Current status: approved');
});

it('returns 422 when student cancels a payment that was approved concurrently', function (): void {
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
        'proof_hash' => hash('sha256', 'cancel_race_test'),
        'status' => 'pending',
    ]);

    // Simulate concurrent approval after policy check retrieves the model
    Payment::retrieved(function (Payment $p) use ($payment): void {
        if ($p->getKey() === $payment->getKey() && ! $p->isApproved()) {
            Payment::query()->whereKey($payment->getKey())->update(['status' => 'approved']);
        }
    });

    $this->actingAs($data['student'], 'sanctum')
        ->postJson('/api/v1/payments/'.$payment->getKey().'/cancel')
        ->assertStatus(422)
        ->assertJsonPath('message', 'Only pending payments can be cancelled. Current status: approved');
});

it('rejects payment submission when student already has an active enrollment', function (): void {
    Storage::fake('local');
    $data = setupPaymentTestData();

    Enrollment::create([
        'user_id' => $data['student']->getKey(),
        'course_id' => $data['course']->getKey(),
        'term_id' => $data['term']->getKey(),
        'source' => 'admin_grant',
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonths(2),
    ]);

    $proof = UploadedFile::fake()->image('receipt.jpg', 800, 600);

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

it('cleans up stored proof file if payment creation fails', function (): void {
    Storage::fake('local');
    $data = setupPaymentTestData();

    $proof = UploadedFile::fake()->image('receipt.jpg', 800, 600);

    Payment::creating(function (): void {
        throw new RuntimeException('Database failure during payment creation');
    });

    expect(fn () => app(SubmitPayment::class)->handle(
        $data['student'],
        $data['course'],
        $data['term'],
        $proof,
        [
            'method' => 'vodafone_cash',
            'sender_identifier' => '01012345678',
        ]
    ))->toThrow(RuntimeException::class, 'Database failure during payment creation');

    expect(Storage::disk('local')->allFiles('proofs'))->toBeEmpty();
});

it('deletes stored proof file when payment creation fails to prevent orphan files via http', function (): void {
    Storage::fake('local');
    $data = setupPaymentTestData();

    $proof = UploadedFile::fake()->image('receipt.jpg', 800, 600);

    Payment::saving(function () {
        throw new RuntimeException('Database failure on saving payment');
    });

    try {
        $this->actingAs($data['student'], 'sanctum')
            ->postJson('/api/v1/payments', [
                'course_id' => $data['course']->getKey(),
                'term_id' => $data['term']->getKey(),
                'method' => 'vodafone_cash',
                'sender_identifier' => '01012345678',
                'proof' => $proof,
            ]);
    } catch (RuntimeException $e) {
        // Expected
    }

    expect(Storage::disk('local')->allFiles('proofs'))->toBeEmpty();
});

it('serializes concurrent duplicate payment submissions and prevents two pending payments', function (): void {
    Storage::fake('local');
    $data = setupPaymentTestData();

    $proof1 = UploadedFile::fake()->image('receipt1.jpg', 800, 600);
    $proof2 = UploadedFile::fake()->image('receipt2.jpg', 800, 600);

    $submitAction = app(SubmitPayment::class);

    $payment1 = $submitAction->handle($data['student'], $data['course'], $data['term'], $proof1, [
        'method' => 'vodafone_cash',
        'sender_identifier' => '01012345678',
    ]);
    expect($payment1->status)->toBe('pending');

    expect(fn () => $submitAction->handle($data['student'], $data['course'], $data['term'], $proof2, [
        'method' => 'vodafone_cash',
        'sender_identifier' => '01012345678',
    ]))->toThrow(ValidationException::class);

    expect(Payment::query()->where('user_id', $data['student']->getKey())->count())->toBe(1);
});

it('rejects payment submission for a term that has already ended', function (): void {
    Storage::fake('local');
    $data = setupPaymentTestData();

    $endedTerm = Term::create([
        'name' => ['ar' => 'فصل منتهي', 'en' => 'Ended Term'],
        'starts_at' => now()->subMonths(6),
        'ends_at' => now()->subDays(5),
        'is_current' => false,
        'sort_order' => 99,
    ]);

    $proof = UploadedFile::fake()->image('receipt.jpg', 800, 600);

    $response = $this->actingAs($data['student'], 'sanctum')
        ->postJson('/api/v1/payments', [
            'course_id' => $data['course']->getKey(),
            'term_id' => $endedTerm->getKey(),
            'method' => 'vodafone_cash',
            'sender_identifier' => '01012345678',
            'proof' => $proof,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['term_id']);
});

it('rejects payment submission with an invalid payment method', function (): void {
    Storage::fake('local');
    $data = setupPaymentTestData();

    $proof = UploadedFile::fake()->image('receipt.jpg', 800, 600);

    $response = $this->actingAs($data['student'], 'sanctum')
        ->postJson('/api/v1/payments', [
            'course_id' => $data['course']->getKey(),
            'term_id' => $data['term']->getKey(),
            'method' => 'unsupported_method',
            'sender_identifier' => '01012345678',
            'proof' => $proof,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['method'])
        ->assertJsonPath('errors.method.0', 'طريقة الدفع المختارة غير صالحة.');
});
