<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Discount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function adminUserForDiscounts(): User
{
    $permission = Permission::findOrCreate('courses.manage', 'web');
    $role = Role::findOrCreate('admin', 'web');
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('allows a permitted admin to create, update, and delete a discount', function (): void {
    $admin = adminUserForDiscounts();
    $course = Course::create([
        'slug' => 'test-discount-course',
        'title' => ['ar' => 'مقرر للخصم', 'en' => 'Course for Discount'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 20000,
        'status' => 'published',
    ]);

    $createResponse = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/discounts', [
        'name' => ['ar' => 'خصم الافتتاح', 'en' => 'Opening Discount'],
        'type' => 'percent',
        'value' => 25,
        'scope' => 'courses',
        'starts_at' => '2026-10-01',
        'ends_at' => '2026-10-31',
        'is_active' => true,
        'course_ids' => [$course->id],
    ]);

    $discountId = $createResponse->assertCreated()
        ->assertJsonPath('data.type', 'percent')
        ->assertJsonPath('data.value', 25)
        ->assertJsonPath('data.scope', 'courses')
        ->assertJsonPath('data.name.ar', 'خصم الافتتاح')
        ->assertJsonPath('data.name.en', 'Opening Discount')
        ->assertJsonPath('data.course_ids.0', $course->id)
        ->json('data.id');

    $this->assertDatabaseHas('discounts', [
        'id' => $discountId,
        'type' => 'percent',
        'value' => 25,
        'scope' => 'courses',
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('discount_course', [
        'discount_id' => $discountId,
        'course_id' => $course->id,
    ]);

    // Update discount to fixed type and all courses
    $updateResponse = $this->actingAs($admin, 'sanctum')->putJson('/api/v1/admin/discounts/'.$discountId, [
        'name' => ['ar' => 'خصم محدث', 'en' => 'Updated Discount'],
        'type' => 'fixed',
        'value' => 5000,
        'scope' => 'all',
    ]);

    $updateResponse->assertOk()
        ->assertJsonPath('data.type', 'fixed')
        ->assertJsonPath('data.value', 5000)
        ->assertJsonPath('data.scope', 'all')
        ->assertJsonPath('data.name.en', 'Updated Discount');

    $this->assertDatabaseHas('discounts', [
        'id' => $discountId,
        'type' => 'fixed',
        'value' => 5000,
        'scope' => 'all',
    ]);

    $this->assertDatabaseMissing('discount_course', [
        'discount_id' => $discountId,
    ]);

    // Delete discount
    $this->actingAs($admin, 'sanctum')->deleteJson('/api/v1/admin/discounts/'.$discountId)
        ->assertNoContent();

    $this->assertDatabaseMissing('discounts', ['id' => $discountId]);
});

it('denies discount management to a student', function (): void {
    $student = User::factory()->create();
    $student->assignRole(Role::findOrCreate('student', 'web'));

    $this->actingAs($student, 'sanctum')->postJson('/api/v1/admin/discounts', [
        'name' => ['ar' => 'ممنوع', 'en' => 'Blocked'],
        'type' => 'percent',
        'value' => 10,
        'scope' => 'all',
        'starts_at' => '2026-10-01',
        'ends_at' => '2026-10-31',
    ])->assertForbidden();

    $this->actingAs($student, 'sanctum')->getJson('/api/v1/admin/discounts')
        ->assertForbidden();
});

it('lists discounts with pagination for a permitted admin', function (): void {
    $admin = adminUserForDiscounts();

    Discount::create([
        'name' => ['ar' => 'خصم أول', 'en' => 'First Discount'],
        'type' => 'percent',
        'value' => 10,
        'scope' => 'all',
        'starts_at' => '2026-10-01',
        'ends_at' => '2026-10-31',
        'is_active' => true,
    ]);

    Discount::create([
        'name' => ['ar' => 'خصم ثان', 'en' => 'Second Discount'],
        'type' => 'fixed',
        'value' => 2000,
        'scope' => 'all',
        'starts_at' => '2026-10-01',
        'ends_at' => '2026-10-31',
        'is_active' => false,
    ]);

    $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/discounts')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

it('validates discount input parameters', function (): void {
    $admin = adminUserForDiscounts();

    // Missing required fields
    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/discounts', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'type', 'value', 'scope', 'starts_at', 'ends_at']);

    // Percent value > 100
    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/discounts', [
        'name' => ['ar' => 'خصم', 'en' => 'Discount'],
        'type' => 'percent',
        'value' => 150,
        'scope' => 'all',
        'starts_at' => '2026-10-01',
        'ends_at' => '2026-10-31',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['value']);

    // ends_at before starts_at
    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/discounts', [
        'name' => ['ar' => 'خصم', 'en' => 'Discount'],
        'type' => 'percent',
        'value' => 20,
        'scope' => 'all',
        'starts_at' => '2026-10-31',
        'ends_at' => '2026-10-01',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['ends_at']);

    // scope=courses requires course_ids
    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/discounts', [
        'name' => ['ar' => 'خصم', 'en' => 'Discount'],
        'type' => 'percent',
        'value' => 20,
        'scope' => 'courses',
        'starts_at' => '2026-10-01',
        'ends_at' => '2026-10-31',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['course_ids']);
});
