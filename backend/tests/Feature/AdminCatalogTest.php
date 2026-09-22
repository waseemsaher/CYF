<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function adminUserForCatalog(): User
{
    $permission = Permission::findOrCreate('courses.manage', 'web');
    $role = Role::findOrCreate('admin', 'web');
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('allows a permitted admin to create, update, and delete a course', function (): void {
    $admin = adminUserForCatalog();
    $year = AcademicYear::create(['name' => ['ar' => 'الأولى', 'en' => 'First'], 'sort_order' => 1]);
    $department = Department::create(['code' => 'CS', 'name' => ['ar' => 'حاسب', 'en' => 'CS'], 'sort_order' => 1]);

    $createResponse = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/courses', [
        'slug' => 'new-course',
        'title' => ['ar' => 'دورة جديدة', 'en' => 'New Course'],
        'description' => ['ar' => 'وصف جديد', 'en' => 'New description'],
        'price_cents' => 25000,
        'status' => 'draft',
        'sort_order' => 1,
        'audiences' => [['academic_year_id' => $year->id, 'department_id' => $department->id]],
    ]);

    $courseId = $createResponse->assertCreated()
        ->assertJsonPath('data.slug', 'new-course')
        ->json('data.id');

    $this->assertDatabaseHas('course_audiences', [
        'course_id' => $courseId,
        'academic_year_id' => $year->id,
        'department_id' => $department->id,
    ]);

    $this->actingAs($admin, 'sanctum')->putJson('/api/v1/admin/courses/'.$courseId, [
        'title' => ['ar' => 'دورة محدثة', 'en' => 'Updated Course'],
        'price_cents' => 30000,
        'status' => 'published',
    ])->assertOk()
        ->assertJsonPath('data.title.en', 'Updated Course')
        ->assertJsonPath('data.price_cents', 30000);

    $this->actingAs($admin, 'sanctum')->deleteJson('/api/v1/admin/courses/'.$courseId)
        ->assertNoContent();

    $this->assertSoftDeleted('courses', ['id' => $courseId]);
});

it('denies course management to a student', function (): void {
    $student = User::factory()->create();
    $student->assignRole(Role::findOrCreate('student', 'web'));

    $this->actingAs($student, 'sanctum')->postJson('/api/v1/admin/courses', [
        'slug' => 'blocked-course',
        'title' => ['ar' => 'ممنوع', 'en' => 'Blocked'],
        'description' => ['ar' => 'ممنوع', 'en' => 'Blocked'],
        'price_cents' => 1000,
        'status' => 'draft',
    ])->assertForbidden();
});

it('lists draft and published courses for a permitted admin', function (): void {
    $admin = adminUserForCatalog();

    Course::create([
        'slug' => 'draft-admin-course',
        'title' => ['ar' => 'مسودة', 'en' => 'Draft'],
        'description' => ['ar' => 'وصف', 'en' => 'Description'],
        'price_cents' => 1000,
        'status' => 'draft',
    ]);

    Course::create([
        'slug' => 'published-admin-course',
        'title' => ['ar' => 'منشورة', 'en' => 'Published'],
        'description' => ['ar' => 'وصف', 'en' => 'Description'],
        'price_cents' => 2000,
        'status' => 'published',
    ]);

    $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/courses')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.slug', 'draft-admin-course');
});
