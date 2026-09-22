<?php

declare(strict_types=1);

use App\Domain\Learning\Actions\CreateCourseItem;
use App\Domain\Learning\Actions\CreateSection;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
});

it('returns locked outline with hidden urls and files for unauthenticated or non-enrolled users', function (): void {
    $course = Course::create([
        'slug' => 'cpp-programming',
        'title' => ['ar' => 'برمجة C++', 'en' => 'C++ Programming'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 20000,
        'telegram_invite_link' => 'https://t.me/+secretlink',
    ]);

    $sectionAction = app(CreateSection::class);
    $section = $sectionAction->handle($course, ['ar' => 'المقدمة', 'en' => 'Introduction']);

    $itemAction = app(CreateCourseItem::class);
    $itemAction->handle(
        $course,
        $section,
        'lecture_link',
        ['ar' => 'محاضرة 1', 'en' => 'Lecture 1'],
        ['ar' => 'شرح المتغيرات', 'en' => 'Variables explanation'],
        'https://t.me/c/123/456'
    );

    $response = $this->getJson("/api/v1/courses/{$course->slug}/content");

    $response->assertOk()
        ->assertJsonPath('data.is_unlocked', false)
        ->assertJsonPath('data.course.telegram_invite_link', null)
        ->assertJsonPath('data.sections.0.items.0.is_locked', true)
        ->assertJsonMissingPath('data.sections.0.items.0.url');
});

it('unlocks full content, urls, and telegram invite link for actively enrolled students', function (): void {
    $course = Course::create([
        'slug' => 'cpp-programming',
        'title' => ['ar' => 'برمجة C++', 'en' => 'C++ Programming'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 20000,
        'telegram_invite_link' => 'https://t.me/+secretlink',
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل 1', 'en' => 'Term 1'],
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonths(3),
        'is_current' => true,
    ]);

    $student = User::factory()->create();

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonths(3),
    ]);

    $sectionAction = app(CreateSection::class);
    $section = $sectionAction->handle($course, ['ar' => 'المقدمة', 'en' => 'Introduction']);

    $itemAction = app(CreateCourseItem::class);
    $itemAction->handle(
        $course,
        $section,
        'lecture_link',
        ['ar' => 'محاضرة 1', 'en' => 'Lecture 1'],
        ['ar' => 'شرح المتغيرات', 'en' => 'Variables explanation'],
        'https://t.me/c/123/456'
    );

    $response = $this->actingAs($student)->getJson("/api/v1/courses/{$course->slug}/content");

    $response->assertOk()
        ->assertJsonPath('data.is_unlocked', true)
        ->assertJsonPath('data.course.telegram_invite_link', 'https://t.me/+secretlink')
        ->assertJsonPath('data.sections.0.items.0.is_locked', false)
        ->assertJsonPath('data.sections.0.items.0.url', 'https://t.me/c/123/456');
});

it('restricts file download to actively enrolled students', function (): void {
    $course = Course::create([
        'slug' => 'discrete-math',
        'title' => ['ar' => 'رياضيات متقطعة', 'en' => 'Discrete Math'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 15000,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $section = app(CreateSection::class)->handle($course, ['ar' => 'فصل 1', 'en' => 'Chapter 1']);

    $dummyFile = UploadedFile::fake()->create('lecture1.pdf', 500, 'application/pdf');
    $item = app(CreateCourseItem::class)->handle(
        $course,
        $section,
        'file',
        ['ar' => 'ملف الشرح', 'en' => 'Slides'],
        null,
        null,
        $dummyFile
    );

    $otherUser = User::factory()->create();
    $enrolledStudent = User::factory()->create();

    Enrollment::create([
        'user_id' => $enrolledStudent->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonths(2),
    ]);

    // Other user should get 403
    $this->actingAs($otherUser)
        ->getJson("/api/v1/courses/{$course->slug}/items/{$item->id}/file")
        ->assertStatus(403);

    // Enrolled student gets file
    $downloadResponse = $this->actingAs($enrolledStudent)
        ->get("/api/v1/courses/{$course->slug}/items/{$item->id}/file");

    $downloadResponse->assertOk();
});
