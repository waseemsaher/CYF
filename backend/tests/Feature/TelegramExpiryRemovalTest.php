<?php

declare(strict_types=1);

use App\Domain\Telegram\Actions\RemoveExpiredMembers;
use App\Models\Course;
use App\Models\CourseItem;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('telegram.bot_token', 'test_token');
    Http::fake();
});

it('removes members whose enrollments are expired or revoked', function (): void {
    $course = Course::create([
        'slug' => 'math',
        'title' => ['ar' => 'رياضيات', 'en' => 'Math'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 10000,
        'telegram_channel_id' => -10099887766,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subMonths(3),
        'ends_at' => now()->subDay(),
        'is_current' => false,
    ]);

    // Student 1: Expired enrollment
    $student1 = User::factory()->create(['telegram_user_id' => 111111]);
    Enrollment::create([
        'user_id' => $student1->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subMonths(3),
        'expires_at' => now()->subDay(),
    ]);

    // Student 2: Revoked enrollment
    $student2 = User::factory()->create(['telegram_user_id' => 222222]);
    Enrollment::create([
        'user_id' => $student2->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'revoked',
        'starts_at' => now()->subMonths(3),
        'expires_at' => now()->addMonth(),
    ]);

    // Student 3: Active valid enrollment (should NOT be removed)
    $student3 = User::factory()->create(['telegram_user_id' => 333333]);
    Enrollment::create([
        'user_id' => $student3->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDays(10),
        'expires_at' => now()->addMonth(),
    ]);

    $action = app(RemoveExpiredMembers::class);
    $removedCount = $action->handle();

    expect($removedCount)->toBe(2);

    // Should call banChatMember then unbanChatMember for student1 and student2
    Http::assertSent(fn ($req) => str_contains($req->url(), 'banChatMember') && $req['user_id'] === 111111);
    Http::assertSent(fn ($req) => str_contains($req->url(), 'unbanChatMember') && $req['user_id'] === 111111);
    Http::assertSent(fn ($req) => str_contains($req->url(), 'banChatMember') && $req['user_id'] === 222222);
    Http::assertSent(fn ($req) => str_contains($req->url(), 'unbanChatMember') && $req['user_id'] === 222222);

    // Student 3 should NEVER be banned
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'banChatMember') && $req['user_id'] === 333333);
});

it('does not remove member if they have another active enrollment for the same course', function (): void {
    $course = Course::create([
        'slug' => 'math',
        'title' => ['ar' => 'رياضيات', 'en' => 'Math'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 10000,
        'telegram_channel_id' => -10099887766,
    ]);

    $pastTerm = Term::create([
        'name' => ['ar' => 'فصل سابق', 'en' => 'Past Term'],
        'starts_at' => now()->subMonths(6),
        'ends_at' => now()->subMonths(2),
        'is_current' => false,
    ]);

    $currentTerm = Term::create([
        'name' => ['ar' => 'فصل حالي', 'en' => 'Current Term'],
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->addMonths(3),
        'is_current' => true,
    ]);

    $student = User::factory()->create(['telegram_user_id' => 444444]);

    // Old expired enrollment
    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $pastTerm->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subMonths(6),
        'expires_at' => now()->subMonths(2),
    ]);

    // Renewed active enrollment
    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $currentTerm->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDays(5),
        'expires_at' => now()->addMonths(3),
    ]);

    $action = app(RemoveExpiredMembers::class);
    $removedCount = $action->handle();

    expect($removedCount)->toBe(0);
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'banChatMember'));
});

it('settles enrollment expiry: status becomes expired, content returns 403, and Telegram removal is invoked', function (): void {
    Role::findOrCreate('student', 'web');

    $course = Course::create([
        'slug' => 'cs-security',
        'title' => ['ar' => 'أمن المعلومات', 'en' => 'InfoSec'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_channel_id' => -10011223344,
        'telegram_group_id' => -10011223355,
    ]);

    $section = CourseSection::create([
        'course_id' => $course->id,
        'title' => ['ar' => 'الفصل 1', 'en' => 'Chapter 1'],
        'position' => 1,
    ]);

    $item = CourseItem::create([
        'course_id' => $course->id,
        'section_id' => $section->id,
        'type' => 'file',
        'title' => ['ar' => 'ملف الشرح', 'en' => 'Notes PDF'],
        'file_path' => 'courses/1/notes.pdf',
        'position' => 1,
        'is_published' => true,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل منتهي', 'en' => 'Expired Term'],
        'starts_at' => now()->subMonths(4),
        'ends_at' => now()->subDays(2),
        'is_current' => false,
    ]);

    $student = User::factory()->create([
        'telegram_user_id' => 999888777,
    ]);
    $student->assignRole('student');

    // 1. Initially enrollment is active but has passed expires_at
    $enrollment = Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subMonths(4),
        'expires_at' => now()->subHour(),
    ]);

    // 2. Student attempts to access protected content -> 403 Forbidden!
    $this->actingAs($student, 'sanctum')
        ->getJson("/api/v1/courses/{$course->slug}/items/{$item->id}/file")
        ->assertForbidden();

    // Also check course overview shows is_unlocked = false
    $overviewRes = $this->actingAs($student, 'sanctum')
        ->getJson("/api/v1/courses/{$course->slug}/content")
        ->assertOk();
    expect($overviewRes->json('data.is_unlocked'))->toBeFalse()
        ->and($overviewRes->json('data.course.telegram_invite_link'))->toBeNull();

    // 3. Automated expiry sweep runs (via Artisan or action)
    $this->artisan('telegram:remove-expired')
        ->expectsOutputToContain('Removed 1 member(s)')
        ->assertSuccessful();

    // 4. Assert status has transitioned to 'expired'
    $enrollment->refresh();
    expect($enrollment->status)->toBe('expired');

    // 5. Assert Telegram removal was invoked for the chats
    Http::assertSent(fn ($req) => str_contains($req->url(), 'banChatMember')
        && $req['user_id'] === 999888777
        && (int) $req['chat_id'] === -10011223344);
    Http::assertSent(fn ($req) => str_contains($req->url(), 'banChatMember')
        && $req['user_id'] === 999888777
        && (int) $req['chat_id'] === -10011223355);
});
