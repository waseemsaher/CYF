<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\CourseItem;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('superadmin', 'web');
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('teacher', 'web');
    Role::findOrCreate('student', 'web');
});

function setupTeacherContentTestData(): array
{
    $term = Term::create([
        'name' => ['ar' => 'الفصل الأول', 'en' => 'Term 1'],
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonths(3),
        'is_current' => true,
        'sort_order' => 1,
    ]);

    $course = Course::create([
        'title' => ['ar' => 'مقدمة في البرمجة', 'en' => 'Intro to Programming'],
        'description' => ['ar' => 'وصف المادة', 'en' => 'Course description'],
        'slug' => 'intro-programming',
        'term_id' => $term->id,
        'price_cents' => 15000,
        'status' => 'published',
    ]);

    $otherCourse = Course::create([
        'title' => ['ar' => 'هياكل البيانات', 'en' => 'Data Structures'],
        'description' => ['ar' => 'وصف هياكل البيانات', 'en' => 'Data structures description'],
        'slug' => 'data-structures',
        'term_id' => $term->id,
        'price_cents' => 20000,
        'status' => 'published',
    ]);

    $assignedTeacher = User::factory()->create(['email' => 'assigned.teacher@codeera.tech']);
    $assignedTeacher->assignRole('teacher');
    $course->teachers()->attach($assignedTeacher->id, ['teacher_share_percent' => 75]);

    $unassignedTeacher = User::factory()->create(['email' => 'unassigned.teacher@codeera.tech']);
    $unassignedTeacher->assignRole('teacher');

    $student = User::factory()->create(['email' => 'student.test@codeera.tech']);
    $student->assignRole('student');

    return compact('term', 'course', 'otherCourse', 'assignedTeacher', 'unassignedTeacher', 'student');
}

test('assigned teacher can view course content outline', function (): void {
    $data = setupTeacherContentTestData();

    $section = CourseSection::create([
        'course_id' => $data['course']->id,
        'title' => ['ar' => 'الوحدة الأولى', 'en' => 'Unit 1'],
        'position' => 1,
    ]);

    CourseItem::create([
        'course_id' => $data['course']->id,
        'section_id' => $section->id,
        'type' => 'lecture_link',
        'title' => ['ar' => 'المحاضرة الأولى', 'en' => 'Lecture 1'],
        'url' => 'https://t.me/c/12345/10',
        'telegram_message_id' => 10,
        'is_free' => true,
        'position' => 1,
    ]);

    $response = $this->actingAs($data['assignedTeacher'], 'sanctum')
        ->getJson("/api/v1/teacher/courses/{$data['course']->id}/content");

    $response->assertOk()
        ->assertJsonPath('data.course.id', $data['course']->id)
        ->assertJsonCount(1, 'data.sections')
        ->assertJsonPath('data.sections.0.items.0.is_free', true)
        ->assertJsonPath('data.sections.0.items.0.telegram_message_id', 10);
});

test('unassigned teacher is forbidden from accessing or managing course content', function (): void {
    $data = setupTeacherContentTestData();

    $this->actingAs($data['unassignedTeacher'], 'sanctum')
        ->getJson("/api/v1/teacher/courses/{$data['course']->id}/content")
        ->assertForbidden();

    $this->actingAs($data['unassignedTeacher'], 'sanctum')
        ->postJson("/api/v1/teacher/courses/{$data['course']->id}/sections", [
            'title' => ['ar' => 'الوحدة غير المصرح بها'],
        ])
        ->assertForbidden();
});

test('assigned teacher can create, update, and delete sections', function (): void {
    $data = setupTeacherContentTestData();

    // Create section
    $createRes = $this->actingAs($data['assignedTeacher'], 'sanctum')
        ->postJson("/api/v1/teacher/courses/{$data['course']->id}/sections", [
            'title' => [
                'ar' => 'القسم الأول - أساسيات',
                'en' => 'Section 1 - Basics',
            ],
            'position' => 1,
        ]);

    $createRes->assertCreated()
        ->assertJsonPath('data.title.ar', 'القسم الأول - أساسيات');

    $sectionId = (int) $createRes->json('data.id');

    // Update section
    $updateRes = $this->actingAs($data['assignedTeacher'], 'sanctum')
        ->putJson("/api/v1/teacher/courses/{$data['course']->id}/sections/{$sectionId}", [
            'title' => [
                'ar' => 'القسم الأول - أساسيات محدث',
            ],
            'position' => 2,
        ]);

    $updateRes->assertOk()
        ->assertJsonPath('data.title.ar', 'القسم الأول - أساسيات محدث')
        ->assertJsonPath('data.position', 2);

    // Delete section
    $deleteRes = $this->actingAs($data['assignedTeacher'], 'sanctum')
        ->deleteJson("/api/v1/teacher/courses/{$data['course']->id}/sections/{$sectionId}");

    $deleteRes->assertOk()
        ->assertJsonPath('message', 'Section deleted successfully.');

    expect(CourseSection::query()->find($sectionId))->toBeNull();
});

test('assigned teacher can create, update, and delete course items with is_free and telegram_message_id', function (): void {
    $data = setupTeacherContentTestData();

    $section = CourseSection::create([
        'course_id' => $data['course']->id,
        'title' => ['ar' => 'الفصل الأول', 'en' => 'Chapter 1'],
        'position' => 1,
    ]);

    // Create item
    $createRes = $this->actingAs($data['assignedTeacher'], 'sanctum')
        ->postJson("/api/v1/teacher/courses/{$data['course']->id}/sections/{$section->id}/items", [
            'type' => 'lecture_link',
            'title' => [
                'ar' => 'مقدمة تجريبية مجانية',
                'en' => 'Free Intro Lecture',
            ],
            'description' => [
                'ar' => 'فيديو تعريفي متاح لجميع الطلاب',
            ],
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'telegram_message_id' => 99,
            'is_free' => true,
            'position' => 1,
        ]);

    $createRes->assertCreated()
        ->assertJsonPath('data.title.ar', 'مقدمة تجريبية مجانية')
        ->assertJsonPath('data.is_free', true)
        ->assertJsonPath('data.telegram_message_id', 99);

    $itemId = (int) $createRes->json('data.id');

    // Update item
    $updateRes = $this->actingAs($data['assignedTeacher'], 'sanctum')
        ->putJson("/api/v1/teacher/courses/{$data['course']->id}/items/{$itemId}", [
            'title' => [
                'ar' => 'مقدمة تجريبية محدثة',
            ],
            'is_free' => false,
        ]);

    $updateRes->assertOk()
        ->assertJsonPath('data.title.ar', 'مقدمة تجريبية محدثة')
        ->assertJsonPath('data.is_free', false);

    // Delete item
    $this->actingAs($data['assignedTeacher'], 'sanctum')
        ->deleteJson("/api/v1/teacher/courses/{$data['course']->id}/items/{$itemId}")
        ->assertOk();

    expect(CourseItem::query()->find($itemId))->toBeNull();
});

test('public course content exposes is_free items to unenrolled students while locking private items', function (): void {
    $data = setupTeacherContentTestData();

    $section = CourseSection::create([
        'course_id' => $data['course']->id,
        'title' => ['ar' => 'المحتوى العام', 'en' => 'General Content'],
        'position' => 1,
    ]);

    $freeItem = CourseItem::create([
        'course_id' => $data['course']->id,
        'section_id' => $section->id,
        'type' => 'lecture_link',
        'title' => ['ar' => 'فيديو مجاني تمهيدي', 'en' => 'Free Preview Video'],
        'url' => 'https://www.youtube.com/watch?v=preview_video_id',
        'is_free' => true,
        'is_published' => true,
        'position' => 1,
    ]);

    $paidItem = CourseItem::create([
        'course_id' => $data['course']->id,
        'section_id' => $section->id,
        'type' => 'file',
        'title' => ['ar' => 'الكتاب الكامل للمادة', 'en' => 'Full Course Book'],
        'url' => 'https://t.me/c/private_book/55',
        'is_free' => false,
        'is_published' => true,
        'position' => 2,
    ]);

    // Unauthenticated guest request
    $guestResponse = $this->getJson("/api/v1/courses/{$data['course']->slug}/content");

    $guestResponse->assertOk()
        ->assertJsonPath('data.is_unlocked', false)
        ->assertJsonPath('data.sections.0.items.0.id', $freeItem->id)
        ->assertJsonPath('data.sections.0.items.0.is_free', true)
        ->assertJsonPath('data.sections.0.items.0.is_locked', false)
        ->assertJsonPath('data.sections.0.items.0.url', 'https://www.youtube.com/watch?v=preview_video_id')
        ->assertJsonPath('data.sections.0.items.1.id', $paidItem->id)
        ->assertJsonPath('data.sections.0.items.1.is_free', false)
        ->assertJsonPath('data.sections.0.items.1.is_locked', true)
        ->assertJsonPath('data.sections.0.items.1.url', null);

    // Enrolled student request
    Enrollment::create([
        'user_id' => $data['student']->id,
        'course_id' => $data['course']->id,
        'term_id' => $data['term']->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonth(),
    ]);

    $studentResponse = $this->actingAs($data['student'], 'sanctum')
        ->getJson("/api/v1/courses/{$data['course']->slug}/content");

    $studentResponse->assertOk()
        ->assertJsonPath('data.is_unlocked', true)
        ->assertJsonPath('data.sections.0.items.0.is_locked', false)
        ->assertJsonPath('data.sections.0.items.1.is_locked', false)
        ->assertJsonPath('data.sections.0.items.1.url', 'https://t.me/c/private_book/55');
});
