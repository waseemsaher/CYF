<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\CourseItem;
use App\Models\CourseSection;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
    Storage::fake('local');
});

test('admin can list, create, assign teachers and record payouts', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $course = Course::create([
        'slug' => 'test-cs101',
        'title' => ['ar' => 'علوم الحاسب', 'en' => 'Computer Science'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 15000,
    ]);

    // 1. Create Teacher
    $createResponse = $this->actingAs($admin)->postJson('/api/v1/admin/teachers', [
        'name' => 'د. حسام عادل',
        'email' => 'hossam@example.com',
        'password' => 'SecurePass123!',
        'phone' => '01012345678',
    ]);

    $createResponse->assertStatus(201)
        ->assertJsonPath('data.name', 'د. حسام عادل')
        ->assertJsonPath('data.email', 'hossam@example.com');

    $teacherId = $createResponse->json('data.id');
    $teacher = User::findOrFail($teacherId);
    expect($teacher->hasRole('teacher'))->toBeTrue();

    // 2. Assign Teacher to Course
    $assignResponse = $this->actingAs($admin)->postJson("/api/v1/admin/courses/{$course->id}/teachers", [
        'teacher_id' => $teacherId,
        'teacher_share_percent' => 75,
    ]);

    $assignResponse->assertOk()
        ->assertJsonPath('message', 'Teacher assigned successfully.');

    expect($course->teachers()->where('teacher_id', $teacherId)->exists())->toBeTrue();

    // 3. List Teachers
    $listResponse = $this->actingAs($admin)->getJson('/api/v1/admin/teachers');
    $listResponse->assertOk()
        ->assertJsonFragment([
            'id' => $teacherId,
            'name' => 'د. حسام عادل',
            'email' => 'hossam@example.com',
        ]);

    // 4. Record Payout
    $payoutResponse = $this->actingAs($admin)->postJson("/api/v1/admin/teachers/{$teacherId}/payouts", [
        'amount_cents' => 50000,
        'note' => 'الدفعة الأولى لشهر أكتوبر',
    ]);

    $payoutResponse->assertStatus(201)
        ->assertJsonPath('data.amount_cents', 50000)
        ->assertJsonPath('data.note', 'الدفعة الأولى لشهر أكتوبر');
});

test('admin can manage course sections, items, quizzes and questions', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $course = Course::create([
        'slug' => 'algorithms-201',
        'title' => ['ar' => 'خوارزميات متقدمة', 'en' => 'Advanced Algorithms'],
        'description' => ['ar' => 'وصف تفصيلي', 'en' => 'Detailed Desc'],
        'status' => 'published',
        'price_cents' => 20000,
    ]);

    // 1. Create Section
    $secResponse = $this->actingAs($admin)->postJson("/api/v1/admin/courses/{$course->id}/sections", [
        'title' => ['ar' => 'الفصل الأول', 'en' => 'Chapter 1'],
        'position' => 1,
    ]);

    $secResponse->assertStatus(201)
        ->assertJsonPath('data.title.ar', 'الفصل الأول');

    $sectionId = $secResponse->json('data.id');

    // 2. Update Section
    $updateSecResponse = $this->actingAs($admin)->putJson("/api/v1/admin/sections/{$sectionId}", [
        'title' => ['ar' => 'الفصل الأول المطور', 'en' => 'Chapter 1 Enhanced'],
        'position' => 2,
    ]);

    $updateSecResponse->assertOk()
        ->assertJsonPath('data.title.ar', 'الفصل الأول المطور')
        ->assertJsonPath('data.position', 2);

    // 3. Create Quiz for the course
    $quizResponse = $this->actingAs($admin)->postJson("/api/v1/admin/courses/{$course->id}/quizzes", [
        'kind' => 'quiz',
        'title' => ['ar' => 'اختبار المصفوفات', 'en' => 'Arrays Quiz'],
        'duration_minutes' => 45,
        'max_attempts' => 2,
        'results_visibility' => 'immediate',
    ]);

    $quizResponse->assertStatus(201)
        ->assertJsonPath('data.title.ar', 'اختبار المصفوفات')
        ->assertJsonPath('data.duration_minutes', 45);

    $quizId = $quizResponse->json('data.id');

    // 4. Create Question for the Quiz
    $qResponse = $this->actingAs($admin)->postJson("/api/v1/admin/quizzes/{$quizId}/questions", [
        'type' => 'mcq',
        'text' => ['ar' => 'ما هو التعقيد الزمني للبحث الثنائي؟', 'en' => 'What is the time complexity of Binary Search?'],
        'explanation' => ['ar' => 'يتم تقليص مساحة البحث للنصف في كل خطوة', 'en' => 'Search space is halved at each step'],
        'points' => 2,
        'options' => [
            ['text' => ['ar' => 'O(log n)', 'en' => 'O(log n)'], 'is_correct' => true],
            ['text' => ['ar' => 'O(n)', 'en' => 'O(n)'], 'is_correct' => false],
            ['text' => ['ar' => 'O(n^2)', 'en' => 'O(n^2)'], 'is_correct' => false],
        ],
    ]);

    $qResponse->assertStatus(201)
        ->assertJsonPath('data.points', 2)
        ->assertJsonPath('data.options.0.is_correct', true);

    $questionId = $qResponse->json('data.id');

    // 5. Create Items in Section (lecture link, file upload, quiz item)
    // 5a. Lecture Link
    $item1Response = $this->actingAs($admin)->postJson("/api/v1/admin/courses/{$course->id}/sections/{$sectionId}/items", [
        'type' => 'lecture_link',
        'title' => ['ar' => 'المحاضرة الأولى', 'en' => 'Lecture 1'],
        'url' => 'https://t.me/c/12345/678',
        'is_published' => true,
    ]);

    $item1Response->assertStatus(201)
        ->assertJsonPath('data.type', 'lecture_link');

    $item1Id = $item1Response->json('data.id');

    // 5b. File Item
    $fakeFile = UploadedFile::fake()->create('notes.pdf', 500, 'application/pdf');
    $item2Response = $this->actingAs($admin)->postJson("/api/v1/admin/courses/{$course->id}/sections/{$sectionId}/items", [
        'type' => 'file',
        'title' => ['ar' => 'ملخص المحاضرة', 'en' => 'Lecture Notes'],
        'file' => $fakeFile,
        'is_published' => true,
    ]);

    $item2Response->assertStatus(201)
        ->assertJsonPath('data.type', 'file');

    // 5c. Quiz Item
    $item3Response = $this->actingAs($admin)->postJson("/api/v1/admin/courses/{$course->id}/sections/{$sectionId}/items", [
        'type' => 'quiz',
        'title' => ['ar' => 'اختبار الفصل', 'en' => 'Chapter Quiz'],
        'quiz_id' => $quizId,
        'is_published' => true,
    ]);

    $item3Response->assertStatus(201)
        ->assertJsonPath('data.type', 'quiz')
        ->assertJsonPath('data.quiz_id', $quizId);

    // 6. Update Item (publish toggle)
    $toggleResponse = $this->actingAs($admin)->putJson("/api/v1/admin/items/{$item1Id}", [
        'is_published' => false,
    ]);

    $toggleResponse->assertOk()
        ->assertJsonPath('data.is_published', false);

    // 7. Delete Question
    $delQResponse = $this->actingAs($admin)->deleteJson("/api/v1/admin/questions/{$questionId}");
    $delQResponse->assertOk();
    expect(Question::find($questionId))->toBeNull();

    // 8. Delete Section and verify cascade delete of items
    $delSecResponse = $this->actingAs($admin)->deleteJson("/api/v1/admin/sections/{$sectionId}");
    $delSecResponse->assertOk();
    expect(CourseSection::find($sectionId))->toBeNull();
    expect(CourseItem::where('section_id', $sectionId)->count())->toBe(0);
});
