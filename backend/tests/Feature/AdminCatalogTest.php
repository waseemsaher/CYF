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
        'is_general' => false,
        'sort_order' => 1,
        'audiences' => [['academic_year_id' => $year->id, 'department_id' => $department->id]],
    ]);

    $courseId = $createResponse->assertCreated()
        ->assertJsonPath('data.slug', 'new-course')
        ->assertJsonPath('data.is_general', false)
        ->assertJsonPath('data.audiences.0.academic_year_id', $year->id)
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
        'is_general' => true,
    ])->assertOk()
        ->assertJsonPath('data.title.en', 'Updated Course')
        ->assertJsonPath('data.price_cents', 30000)
        ->assertJsonPath('data.is_general', true);

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

it('verifies end-to-end course assignment and filtered catalog visibility for First Year and Second Year students', function (): void {
    $admin = adminUserForCatalog();
    $year1 = AcademicYear::create(['name' => ['ar' => 'السنة الأولى', 'en' => '1st Year'], 'sort_order' => 1]);
    $year2 = AcademicYear::create(['name' => ['ar' => 'السنة الثانية', 'en' => '2nd Year'], 'sort_order' => 2]);
    $dept = Department::create(['code' => 'CS', 'name' => ['ar' => 'علوم الحاسب', 'en' => 'CS'], 'sort_order' => 1]);

    // Admin creates First Year course
    $resY1 = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/courses', [
        'slug' => 'course-first-year',
        'title' => ['ar' => 'كورس السنة الأولى', 'en' => 'First Year Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 15000,
        'status' => 'published',
        'is_general' => false,
        'audiences' => [['academic_year_id' => $year1->id, 'department_id' => $dept->id]],
    ]);
    if ($resY1->status() !== 201) {
        dump($resY1->json());
    }
    $resY1->assertCreated();

    // Admin creates General course
    $resGen = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/courses', [
        'slug' => 'course-general',
        'title' => ['ar' => 'كورس عام', 'en' => 'General Course'],
        'description' => ['ar' => 'وصف عام', 'en' => 'General Desc'],
        'price_cents' => 20000,
        'status' => 'published',
        'is_general' => true,
    ]);
    if ($resGen->status() !== 201) {
        dump($resGen->json());
    }
    $resGen->assertCreated();

    // Register a First Year student
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
    $student1Res = $this->postJson('/api/v1/register', [
        'name' => 'First Year Student',
        'email' => 'student-y1@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'branch' => 'azhar_boys',
        'academic_year_id' => $year1->id,
        'department' => 'CS',
        'phone' => '01011112222',
    ])->assertCreated();

    $y1StudentYearId = $student1Res->json('data.user.academic_year_id');
    expect($y1StudentYearId)->toBe($year1->id);

    // Register a Second Year student
    $student2Res = $this->postJson('/api/v1/register', [
        'name' => 'Second Year Student',
        'email' => 'student-y2@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'branch' => 'azhar_boys',
        'academic_year_id' => $year2->id,
        'department' => 'CS',
        'phone' => '01033334444',
    ])->assertCreated();

    $y2StudentYearId = $student2Res->json('data.user.academic_year_id');
    expect($y2StudentYearId)->toBe($year2->id);

    // First Year student catalog view: sees First Year course + General course
    $y1Catalog = $this->getJson('/api/v1/courses?academic_year_id='.$y1StudentYearId);
    $y1Catalog->assertOk()->assertJsonCount(2, 'data');
    $y1Slugs = collect($y1Catalog->json('data'))->pluck('slug')->all();
    expect($y1Slugs)->toContain('course-first-year', 'course-general');

    // Second Year student catalog view: sees ONLY General course, not First Year course
    $y2Catalog = $this->getJson('/api/v1/courses?academic_year_id='.$y2StudentYearId);
    $y2Catalog->assertOk()->assertJsonCount(1, 'data');
    expect($y2Catalog->json('data.0.slug'))->toBe('course-general');
});
