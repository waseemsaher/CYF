<?php

declare(strict_types=1);

use App\Domain\Telegram\Actions\RemoveExpiredMembers;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

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
