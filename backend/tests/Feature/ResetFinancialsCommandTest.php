<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\TeacherPayout;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('platform:reset-financials clears all payments, subscriptions, and teacher payouts while keeping users', function (): void {
    Storage::fake('local');
    config(['filesystems.default' => 'local']);

    Role::findOrCreate('student', 'web');
    Role::findOrCreate('teacher', 'web');

    $student = User::factory()->create();
    $student->assignRole('student');

    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $term = Term::create([
        'name' => ['ar' => 'الفصل الأول', 'en' => 'Term 1'],
        'starts_at' => now()->startOfDay(),
        'ends_at' => now()->addMonths(4)->endOfDay(),
        'is_current' => true,
        'sort_order' => 1,
    ]);
    $course = Course::create([
        'slug' => 'test-financials-course',
        'title' => ['ar' => 'مقرر مالي', 'en' => 'Financial Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 15000,
        'status' => 'published',
    ]);

    // Create payment with fake proof file
    $proofPath = 'proofs/test_receipt.jpg';
    Storage::disk('local')->put($proofPath, 'dummy image content');

    $payment = Payment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'method' => 'vodafone_cash',
        'list_price_cents' => 15000,
        'discount_cents' => 0,
        'amount_due_cents' => 15000,
        'sender_identifier' => '01012345678',
        'proof_path' => $proofPath,
        'proof_hash' => hash('sha256', 'dummy image content'),
        'status' => 'approved',
        'teacher_share_percent' => 70,
        'teacher_share_cents' => 10500,
        'platform_share_cents' => 4500,
    ]);

    // Create active enrollment
    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'payment_id' => $payment->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonths(3),
    ]);

    // Create teacher payout
    TeacherPayout::create([
        'teacher_id' => $teacher->id,
        'amount_cents' => 5000,
        'paid_at' => now(),
        'note' => 'Partial payout',
        'created_by' => $teacher->id,
    ]);

    expect(Payment::count())->toBe(1);
    expect(Enrollment::count())->toBe(1);
    expect(TeacherPayout::count())->toBe(1);
    expect(Storage::disk('local')->exists($proofPath))->toBeTrue();

    // Run command with --force
    $this->artisan('platform:reset-financials --force')
        ->expectsOutputToContain('Successfully reset all payments, teacher earnings/payouts, and active subscriptions')
        ->assertSuccessful();

    expect(Payment::count())->toBe(0);
    expect(Enrollment::count())->toBe(0);
    expect(TeacherPayout::count())->toBe(0);
    expect(Storage::disk('local')->exists($proofPath))->toBeFalse();

    // Verify student and teacher user accounts are completely preserved
    expect(User::find($student->id))->not->toBeNull();
    expect(User::find($teacher->id))->not->toBeNull();
});
