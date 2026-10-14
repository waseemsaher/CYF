<?php

declare(strict_types=1);

use App\Models\Coupon;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function setupAdminCouponTestEnv(): array
{
    Permission::findOrCreate('coupons.manage', 'web');
    Permission::findOrCreate('courses.manage', 'web');
    Permission::findOrCreate('payments.review', 'web');
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::findOrCreate('superadmin', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo('coupons.manage');

    Role::findOrCreate('student', 'web');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $student = User::factory()->create(['email_verified_at' => now()]);
    $student->assignRole('student');

    return compact('admin', 'student');
}

it('allows admin to list coupons with pagination and redemption count', function (): void {
    ['admin' => $admin] = setupAdminCouponTestEnv();

    Coupon::create([
        'code' => 'COUPON1',
        'type' => 'percent',
        'value' => 10,
        'scope' => 'all',
        'is_active' => true,
    ]);

    Coupon::create([
        'code' => 'COUPON2',
        'type' => 'fixed',
        'value' => 500,
        'scope' => 'all',
        'is_active' => false,
    ]);

    $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/coupons');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'code',
                    'type',
                    'value',
                    'scope',
                    'max_redemptions',
                    'max_redemptions_per_user',
                    'is_active',
                    'times_redeemed',
                    'courses',
                    'created_at',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'total'],
        ]);

    expect($response->json('meta.total'))->toBe(2);
});

it('allows admin to create a coupon and logs activity', function (): void {
    ['admin' => $admin] = setupAdminCouponTestEnv();

    $course = Course::create([
        'slug' => 'test-coupon-admin-course',
        'title' => ['ar' => 'دورة', 'en' => 'Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
        'status' => 'published',
    ]);

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/coupons', [
        'code' => 'newyear2026',
        'type' => 'percent',
        'value' => 25,
        'scope' => 'courses',
        'course_ids' => [$course->id],
        'max_redemptions' => 100,
        'max_redemptions_per_user' => 2,
        'starts_at' => '2026-01-01 00:00:00',
        'ends_at' => '2026-12-31 23:59:59',
        'is_active' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.code', 'NEWYEAR2026')
        ->assertJsonPath('data.type', 'percent')
        ->assertJsonPath('data.value', 25)
        ->assertJsonPath('data.scope', 'courses');

    $coupon = Coupon::where('code', 'NEWYEAR2026')->first();
    expect($coupon)->not->toBeNull()
        ->and($coupon->courses()->count())->toBe(1);

    // Verify activity log
    $activity = Activity::where('log_name', 'coupon')->latest()->first();
    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->subject_id)->toBe($coupon->id);
});

it('allows admin to show coupon details including redemptions history', function (): void {
    ['admin' => $admin, 'student' => $student] = setupAdminCouponTestEnv();

    $coupon = Coupon::create([
        'code' => 'SHOWME',
        'type' => 'percent',
        'value' => 15,
        'scope' => 'all',
        'is_active' => true,
    ]);

    $coupon->redemptions()->create([
        'user_id' => $student->id,
        'payment_id' => null,
    ]);

    $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/admin/coupons/{$coupon->id}");

    $response->assertOk()
        ->assertJsonPath('data.code', 'SHOWME')
        ->assertJsonPath('data.times_redeemed', 1)
        ->assertJsonStructure([
            'data' => [
                'redemptions' => [
                    '*' => ['id', 'user', 'payment', 'created_at'],
                ],
            ],
        ]);
});

it('allows admin to update coupon details and logs activity', function (): void {
    ['admin' => $admin] = setupAdminCouponTestEnv();

    $coupon = Coupon::create([
        'code' => 'UPDATEABLE',
        'type' => 'percent',
        'value' => 10,
        'scope' => 'all',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'sanctum')->putJson("/api/v1/admin/coupons/{$coupon->id}", [
        'code' => 'UPDATED20',
        'type' => 'percent',
        'value' => 20,
        'scope' => 'all',
        'is_active' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.code', 'UPDATED20')
        ->assertJsonPath('data.value', 20);

    expect($coupon->fresh()->code)->toBe('UPDATED20');
});

it('allows admin to toggle active status and logs activity', function (): void {
    ['admin' => $admin] = setupAdminCouponTestEnv();

    $coupon = Coupon::create([
        'code' => 'TOGGLEME',
        'type' => 'percent',
        'value' => 10,
        'scope' => 'all',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/admin/coupons/{$coupon->id}/toggle-active");

    $response->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect($coupon->fresh()->is_active)->toBeFalse();

    // Toggle back
    $response2 = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/admin/coupons/{$coupon->id}/toggle-active");
    $response2->assertOk()
        ->assertJsonPath('data.is_active', true);

    expect($coupon->fresh()->is_active)->toBeTrue();
});

it('allows admin to delete a coupon and logs activity', function (): void {
    ['admin' => $admin] = setupAdminCouponTestEnv();

    $coupon = Coupon::create([
        'code' => 'DELETEME',
        'type' => 'percent',
        'value' => 10,
        'scope' => 'all',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/admin/coupons/{$coupon->id}");

    $response->assertStatus(204);
    expect(Coupon::find($coupon->id))->toBeNull();
});

it('forbids students from managing coupons', function (): void {
    ['student' => $student] = setupAdminCouponTestEnv();

    $response = $this->actingAs($student, 'sanctum')->getJson('/api/v1/admin/coupons');
    $response->assertForbidden();

    $postResponse = $this->actingAs($student, 'sanctum')->postJson('/api/v1/admin/coupons', [
        'code' => 'HACK100',
        'type' => 'percent',
        'value' => 100,
        'scope' => 'all',
    ]);
    $postResponse->assertForbidden();
});
