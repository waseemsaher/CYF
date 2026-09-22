<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Setting;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function setupEnrollmentTestData(): array
{
    Permission::findOrCreate('payments.review', 'web');
    Permission::findOrCreate('courses.manage', 'web');
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $superadminRole = Role::findOrCreate('superadmin', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo('payments.review');
    Role::findOrCreate('student', 'web');

    $student = User::factory()->create(['email_verified_at' => now()]);
    $student->assignRole('student');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $course = Course::create([
        'slug' => 'enroll-test-course',
        'title' => ['ar' => 'دورة تسجيل', 'en' => 'Enrollment Test Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Description'],
        'price_cents' => 20000,
        'status' => 'published',
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل تسجيل', 'en' => 'Enrollment Term'],
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addMonths(3),
        'is_current' => true,
        'sort_order' => 1,
    ]);

    Setting::setValue('enrollment', 'grace_days', 0);

    return compact('student', 'admin', 'course', 'term');
}

it('allows an admin to grant an enrollment without a payment', function (): void {
    $data = setupEnrollmentTestData();

    $response = $this->actingAs($data['admin'], 'sanctum')
        ->postJson('/api/v1/admin/enrollments/grant', [
            'user_id' => $data['student']->getKey(),
            'course_id' => $data['course']->getKey(),
            'term_id' => $data['term']->getKey(),
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.source', 'admin_grant')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.granted_by', $data['admin']->getKey());

    $this->assertDatabaseHas('enrollments', [
        'user_id' => $data['student']->getKey(),
        'course_id' => $data['course']->getKey(),
        'source' => 'admin_grant',
    ]);
});

it('allows an admin to revoke an enrollment', function (): void {
    $data = setupEnrollmentTestData();

    $enrollment = Enrollment::create([
        'user_id' => $data['student']->getKey(),
        'course_id' => $data['course']->getKey(),
        'term_id' => $data['term']->getKey(),
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonths(3),
    ]);

    $response = $this->actingAs($data['admin'], 'sanctum')
        ->postJson('/api/v1/admin/enrollments/' . $enrollment->getKey() . '/revoke');

    $response->assertOk()
        ->assertJsonPath('data.status', 'revoked');
});

it('allows an admin to extend an enrollment', function (): void {
    $data = setupEnrollmentTestData();

    $enrollment = Enrollment::create([
        'user_id' => $data['student']->getKey(),
        'course_id' => $data['course']->getKey(),
        'term_id' => $data['term']->getKey(),
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonths(3),
    ]);

    $newExpiry = now()->addMonths(6)->toISOString();

    $response = $this->actingAs($data['admin'], 'sanctum')
        ->putJson('/api/v1/admin/enrollments/' . $enrollment->getKey() . '/extend', [
            'expires_at' => $newExpiry,
        ]);

    $response->assertOk();
});

it('denies a student from granting enrollments', function (): void {
    $data = setupEnrollmentTestData();

    $this->actingAs($data['student'], 'sanctum')
        ->postJson('/api/v1/admin/enrollments/grant', [
            'user_id' => $data['student']->getKey(),
            'course_id' => $data['course']->getKey(),
            'term_id' => $data['term']->getKey(),
        ])
        ->assertForbidden();
});

it('denies a student from revoking enrollments', function (): void {
    $data = setupEnrollmentTestData();

    $enrollment = Enrollment::create([
        'user_id' => $data['student']->getKey(),
        'course_id' => $data['course']->getKey(),
        'term_id' => $data['term']->getKey(),
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonths(3),
    ]);

    $this->actingAs($data['student'], 'sanctum')
        ->postJson('/api/v1/admin/enrollments/' . $enrollment->getKey() . '/revoke')
        ->assertForbidden();
});
