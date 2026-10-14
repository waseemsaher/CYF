<?php

declare(strict_types=1);

use App\Domain\Catalog\Actions\CalculateCoursePrice;
use App\Domain\Payments\Actions\ApprovePayment;
use App\Domain\Payments\Actions\CancelPayment;
use App\Domain\Payments\Actions\RejectPayment;
use App\Domain\Payments\Actions\SubmitPayment;
use App\Domain\Payments\Actions\ValidateCoupon;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Course;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Payment;
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

beforeEach(function (): void {
    Storage::fake('local');
});

function createDummyImage(): UploadedFile
{
    $image = imagecreatetruecolor(10, 10);
    $tempPath = tempnam(sys_get_temp_dir(), 'test_img_');
    imagejpeg($image, $tempPath);
    imagedestroy($image);

    return new UploadedFile($tempPath, 'proof.jpg', 'image/jpeg', null, true);
}

function setupRoles(): void
{
    Permission::findOrCreate('payments.review', 'web');
    Permission::findOrCreate('courses.manage', 'web');
    Permission::findOrCreate('coupons.manage', 'web');
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::findOrCreate('superadmin', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo(['payments.review', 'coupons.manage', 'courses.manage']);
    Role::findOrCreate('student', 'web');
}

function createStudentUser(string $email = 'student@example.com'): User
{
    setupRoles();
    $user = User::factory()->create([
        'email' => $email,
        'email_verified_at' => now(),
    ]);
    $user->assignRole('student');

    return $user;
}

function createAdminUser(): User
{
    setupRoles();
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'email_verified_at' => now(),
    ]);
    $user->assignRole('superadmin');

    return $user;
}

function createTestCourse(int $priceCents = 10000): Course
{
    return Course::create([
        'slug' => 'coupon-test-course-'.uniqid(),
        'title' => ['ar' => 'دورة تجريبية', 'en' => 'Test Course'],
        'description' => ['ar' => 'وصف تجريبي', 'en' => 'Test description'],
        'price_cents' => $priceCents,
        'status' => 'published',
    ]);
}

function createTestTerm(): Term
{
    return Term::create([
        'name' => ['ar' => 'فصل الخريف', 'en' => 'Fall Term'],
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addDays(60),
    ]);
}

it('valid coupon reduces price correctly for percentage and fixed types', function (): void {
    $course = createTestCourse(10000);

    $percentCoupon = Coupon::create([
        'code' => 'save20',
        'type' => 'percent',
        'value' => 20,
        'scope' => 'all',
        'is_active' => true,
    ]);

    expect($percentCoupon->code)->toBe('SAVE20');

    $calc = app(CalculateCoursePrice::class);
    $resultPercent = $calc->handle($course, coupon: $percentCoupon);

    expect($resultPercent['list_price_cents'])->toBe(10000)
        ->and($resultPercent['discount_cents'])->toBe(2000)
        ->and($resultPercent['amount_due_cents'])->toBe(8000)
        ->and($resultPercent['coupon_id'])->toBe($percentCoupon->id)
        ->and($resultPercent['discount_id'])->toBeNull();

    $fixedCoupon = Coupon::create([
        'code' => 'flat3000',
        'type' => 'fixed',
        'value' => 3000,
        'scope' => 'all',
        'is_active' => true,
    ]);

    $resultFixed = $calc->handle($course, coupon: $fixedCoupon);

    expect($resultFixed['list_price_cents'])->toBe(10000)
        ->and($resultFixed['discount_cents'])->toBe(3000)
        ->and($resultFixed['amount_due_cents'])->toBe(7000)
        ->and($resultFixed['coupon_id'])->toBe($fixedCoupon->id)
        ->and($resultFixed['discount_id'])->toBeNull();
});

it('coupon code overrides automatic discount entirely per decision', function (): void {
    $course = createTestCourse(10000);

    // Auto-discount: 50%
    $course->discounts()->create([
        'name' => ['ar' => 'خصم تلقائي', 'en' => 'Auto Discount'],
        'type' => 'percent',
        'value' => 50,
        'scope' => 'courses',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'is_active' => true,
    ]);

    // Student enters a 10% coupon code
    $coupon = Coupon::create([
        'code' => 'TENTOUCH',
        'type' => 'percent',
        'value' => 10,
        'scope' => 'all',
        'is_active' => true,
    ]);

    $calc = app(CalculateCoursePrice::class);

    // Without coupon: auto-discount 50% applies (5000 cents due)
    $autoResult = $calc->handle($course);
    expect($autoResult['discount_cents'])->toBe(5000)
        ->and($autoResult['amount_due_cents'])->toBe(5000);

    // With coupon: coupon overrides auto-discount (9000 cents due)
    $couponResult = $calc->handle($course, coupon: $coupon);
    expect($couponResult['discount_cents'])->toBe(1000)
        ->and($couponResult['amount_due_cents'])->toBe(9000)
        ->and($couponResult['coupon_id'])->toBe($coupon->id)
        ->and($couponResult['discount_id'])->toBeNull();
});

it('rejects expired, not started, or inactive coupons with Arabic error messages', function (): void {
    $course = createTestCourse(10000);
    $validator = app(ValidateCoupon::class);

    // Inactive
    $inactiveCoupon = Coupon::create([
        'code' => 'INACTIVE',
        'type' => 'percent',
        'value' => 10,
        'scope' => 'all',
        'is_active' => false,
    ]);

    try {
        $validator->handle('INACTIVE', $course);
        $this->fail('Expected ValidationException for inactive coupon');
    } catch (ValidationException $e) {
        expect($e->errors()['coupon_code'][0])->toBe('هذا الكوبون غير مفعّل حاليًا.');
    }

    // Expired
    $expiredCoupon = Coupon::create([
        'code' => 'EXPIRED',
        'type' => 'percent',
        'value' => 10,
        'scope' => 'all',
        'starts_at' => now()->subDays(20),
        'ends_at' => now()->subDay(),
        'is_active' => true,
    ]);

    try {
        $validator->handle('EXPIRED', $course);
        $this->fail('Expected ValidationException for expired coupon');
    } catch (ValidationException $e) {
        expect($e->errors()['coupon_code'][0])->toBe('هذا الكوبون منتهي الصلاحية أو لم يبدأ بعد.');
    }

    // Non-existent
    try {
        $validator->handle('DOESNOTEXIST', $course);
        $this->fail('Expected ValidationException for non-existent coupon');
    } catch (ValidationException $e) {
        expect($e->errors()['coupon_code'][0])->toBe('كوبون الخصم غير صالح أو غير موجود.');
    }
});

it('rejects coupon when scope does not include the course', function (): void {
    $courseA = createTestCourse(10000);
    $courseB = createTestCourse(10000);

    $scopedCoupon = Coupon::create([
        'code' => 'COURSEBONLY',
        'type' => 'percent',
        'value' => 15,
        'scope' => 'courses',
        'is_active' => true,
    ]);
    $scopedCoupon->courses()->attach($courseB->id);

    $validator = app(ValidateCoupon::class);

    // Valid for course B
    $valid = $validator->handle('COURSEBONLY', $courseB);
    expect($valid->id)->toBe($scopedCoupon->id);

    // Invalid for course A
    try {
        $validator->handle('COURSEBONLY', $courseA);
        $this->fail('Expected ValidationException for wrong course scope');
    } catch (ValidationException $e) {
        expect($e->errors()['coupon_code'][0])->toBe('هذا الكوبون غير صالح لهذه المادة.');
    }
});

it('enforces max_redemptions limit across all users', function (): void {
    $course = createTestCourse(10000);
    $term = createTestTerm();
    $student1 = createStudentUser('s1@example.com');
    $student2 = createStudentUser('s2@example.com');

    $coupon = Coupon::create([
        'code' => 'LIMITED1',
        'type' => 'percent',
        'value' => 20,
        'scope' => 'all',
        'max_redemptions' => 1,
        'max_redemptions_per_user' => 1,
        'is_active' => true,
    ]);

    $submit = app(SubmitPayment::class);

    // First student submits payment with LIMITED1
    $payment1 = $submit->handle($student1, $course, $term, createDummyImage(), [
        'method' => 'vodafone_cash',
        'sender_identifier' => '01012345678',
        'coupon_code' => 'LIMITED1',
    ]);

    expect($payment1)->toBeInstanceOf(Payment::class)
        ->and($payment1->coupon_id)->toBe($coupon->id);

    expect(CouponRedemption::where('coupon_id', $coupon->id)->count())->toBe(1);

    // Second student attempts to use LIMITED1
    try {
        $submit->handle($student2, $course, $term, createDummyImage(), [
            'method' => 'vodafone_cash',
            'sender_identifier' => '01087654321',
            'coupon_code' => 'LIMITED1',
        ]);
        $this->fail('Expected max_redemptions validation exception');
    } catch (ValidationException $e) {
        expect($e->errors()['coupon_code'][0])->toBe('تم الوصول للحد الأقصى لاستخدام هذا الكوبون.');
    }
});

it('enforces max_redemptions_per_user limit', function (): void {
    $courseA = createTestCourse(10000);
    $courseB = createTestCourse(10000);
    $term = createTestTerm();
    $student = createStudentUser();

    $coupon = Coupon::create([
        'code' => 'ONCEPERUSER',
        'type' => 'percent',
        'value' => 25,
        'scope' => 'all',
        'max_redemptions' => 10,
        'max_redemptions_per_user' => 1,
        'is_active' => true,
    ]);

    $submit = app(SubmitPayment::class);

    // Submit for course A
    $submit->handle($student, $courseA, $term, createDummyImage(), [
        'method' => 'vodafone_cash',
        'sender_identifier' => '01012345678',
        'coupon_code' => 'ONCEPERUSER',
    ]);

    // Same student attempts to submit for course B using the same coupon
    try {
        $submit->handle($student, $courseB, $term, createDummyImage(), [
            'method' => 'vodafone_cash',
            'sender_identifier' => '01012345678',
            'coupon_code' => 'ONCEPERUSER',
        ]);
        $this->fail('Expected per-user limit validation exception');
    } catch (ValidationException $e) {
        expect($e->errors()['coupon_code'][0])->toBe('لقد استنفدت الحد الأقصى المسموح به لاستخدام هذا الكوبون.');
    }
});

it('payment rejection releases the coupon redemption slot', function (): void {
    $course = createTestCourse(10000);
    $term = createTestTerm();
    $student1 = createStudentUser('s1@example.com');
    $student2 = createStudentUser('s2@example.com');
    $admin = createAdminUser();

    $coupon = Coupon::create([
        'code' => 'RELEASABLE',
        'type' => 'fixed',
        'value' => 2000,
        'scope' => 'all',
        'max_redemptions' => 1,
        'is_active' => true,
    ]);

    $submit = app(SubmitPayment::class);

    // Student 1 reserves the slot
    $payment = $submit->handle($student1, $course, $term, createDummyImage(), [
        'method' => 'vodafone_cash',
        'sender_identifier' => '01012345678',
        'coupon_code' => 'RELEASABLE',
    ]);

    expect(CouponRedemption::where('coupon_id', $coupon->id)->count())->toBe(1);

    // Admin rejects the payment
    app(RejectPayment::class)->handle($payment, $admin, 'Fake receipt');

    // The reservation slot is released
    expect(CouponRedemption::where('coupon_id', $coupon->id)->count())->toBe(0);

    // Student 2 can now successfully use the coupon!
    $payment2 = $submit->handle($student2, $course, $term, createDummyImage(), [
        'method' => 'vodafone_cash',
        'sender_identifier' => '01087654321',
        'coupon_code' => 'RELEASABLE',
    ]);

    expect($payment2->coupon_id)->toBe($coupon->id)
        ->and(CouponRedemption::where('coupon_id', $coupon->id)->count())->toBe(1);
});

it('payment cancellation releases the coupon redemption slot', function (): void {
    $course = createTestCourse(10000);
    $term = createTestTerm();
    $student = createStudentUser('s1@example.com');

    $coupon = Coupon::create([
        'code' => 'CANCELLABLE',
        'type' => 'fixed',
        'value' => 2000,
        'scope' => 'all',
        'max_redemptions' => 1,
        'is_active' => true,
    ]);

    $submit = app(SubmitPayment::class);

    $payment = $submit->handle($student, $course, $term, createDummyImage(), [
        'method' => 'vodafone_cash',
        'sender_identifier' => '01012345678',
        'coupon_code' => 'CANCELLABLE',
    ]);

    expect(CouponRedemption::where('coupon_id', $coupon->id)->count())->toBe(1);

    app(CancelPayment::class)->handle($payment, $student);

    expect(CouponRedemption::where('coupon_id', $coupon->id)->count())->toBe(0);
});

it('payment approval finalizes the payment and maintains the redemption', function (): void {
    $course = createTestCourse(10000);
    $term = createTestTerm();
    $student = createStudentUser('s1@example.com');
    $admin = createAdminUser();

    $coupon = Coupon::create([
        'code' => 'APPROVABLE',
        'type' => 'percent',
        'value' => 50,
        'scope' => 'all',
        'is_active' => true,
    ]);

    $submit = app(SubmitPayment::class);

    $payment = $submit->handle($student, $course, $term, createDummyImage(), [
        'method' => 'vodafone_cash',
        'sender_identifier' => '01012345678',
        'coupon_code' => 'APPROVABLE',
    ]);

    app(ApprovePayment::class)->handle($payment, $admin);

    expect($payment->fresh()->status)->toBe('approved');
    expect(CouponRedemption::where('coupon_id', $coupon->id)->where('payment_id', $payment->id)->count())->toBe(1);
});

it('100% coupon yields free enrollment and creates redemption without payment', function (): void {
    $course = createTestCourse(10000);
    $term = createTestTerm();
    $student = createStudentUser();

    $coupon = Coupon::create([
        'code' => 'FREE100',
        'type' => 'percent',
        'value' => 100,
        'scope' => 'all',
        'max_redemptions' => 1,
        'is_active' => true,
    ]);

    $submit = app(SubmitPayment::class);

    $result = $submit->handle($student, $course, $term, createDummyImage(), [
        'method' => 'vodafone_cash',
        'sender_identifier' => '01012345678',
        'coupon_code' => 'FREE100',
    ]);

    // Should return active Enrollment directly
    expect($result)->toBeInstanceOf(Enrollment::class)
        ->and($result->status)->toBe('active');

    // Redemption recorded with null payment_id
    expect(CouponRedemption::where('coupon_id', $coupon->id)->where('user_id', $student->id)->count())->toBe(1);
    expect($coupon->activeRedemptionsCount())->toBe(1);
});

it('live coupon preview endpoint validates and returns discount pricing', function (): void {
    $course = createTestCourse(10000);
    $student = createStudentUser();

    Coupon::create([
        'code' => 'PREVIEW20',
        'type' => 'percent',
        'value' => 20,
        'scope' => 'all',
        'is_active' => true,
    ]);

    $response = $this->actingAs($student, 'sanctum')->postJson('/api/v1/coupons/validate', [
        'course_id' => $course->id,
        'code' => 'preview20',
    ]);

    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'coupon' => [
                'code' => 'PREVIEW20',
                'type' => 'percent',
                'value' => 20,
            ],
            'pricing' => [
                'list_price_cents' => 10000,
                'discount_cents' => 2000,
                'amount_due_cents' => 8000,
            ],
        ]);
});

it('live coupon preview returns 422 with translated error on invalid code', function (): void {
    $course = createTestCourse(10000);
    $student = createStudentUser();

    $response = $this->actingAs($student, 'sanctum')->postJson('/api/v1/coupons/validate', [
        'course_id' => $course->id,
        'code' => 'INVALIDCODE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['coupon_code']);
});
