<?php

declare(strict_types=1);

use App\Domain\Telegram\Actions\ProcessJoinRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\TelegramCourseInvite;
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

it('approves join requests for students with active valid enrollments', function (): void {
    $course = Course::create([
        'slug' => 'algo',
        'title' => ['ar' => 'خوارزميات', 'en' => 'Algorithms'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_channel_id' => -100555444333,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $student = User::factory()->create(['telegram_user_id' => 11223344]);

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
    ]);

    $action = app(ProcessJoinRequest::class);
    $result = $action->handle(-100555444333, 11223344, 'student_user');

    expect($result)->toBeTrue();
    Http::assertSent(fn ($req) => str_contains($req->url(), 'approveChatJoinRequest'));
});

it('declines join requests for users without enrollment', function (): void {
    $course = Course::create([
        'slug' => 'algo',
        'title' => ['ar' => 'خوارزميات', 'en' => 'Algorithms'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_channel_id' => -100555444333,
    ]);

    $user = User::factory()->create(['telegram_user_id' => 999000111]);

    $action = app(ProcessJoinRequest::class);
    $result = $action->handle(-100555444333, 999000111, 'unauthorized');

    expect($result)->toBeFalse();
    Http::assertSent(fn ($req) => str_contains($req->url(), 'declineChatJoinRequest'));
});

it('declines join requests when enrollment is expired', function (): void {
    $course = Course::create([
        'slug' => 'algo',
        'title' => ['ar' => 'خوارزميات', 'en' => 'Algorithms'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_channel_id' => -100555444333,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subMonths(3),
        'ends_at' => now()->subDay(),
        'is_current' => false,
    ]);

    $student = User::factory()->create(['telegram_user_id' => 888777666]);

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subMonths(3),
        'expires_at' => now()->subDay(),
    ]);

    $action = app(ProcessJoinRequest::class);
    $result = $action->handle(-100555444333, 888777666, 'expired_student');

    expect($result)->toBeFalse();
    Http::assertSent(fn ($req) => str_contains($req->url(), 'declineChatJoinRequest'));
});

it('approves join requests for admin staff even without an enrollment', function (): void {
    Role::create(['name' => 'admin']);

    $course = Course::create([
        'slug' => 'algo',
        'title' => ['ar' => 'خوارزميات', 'en' => 'Algorithms'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_channel_id' => -100555444333,
    ]);

    $admin = User::factory()->create(['telegram_user_id' => 12344321]);
    $admin->assignRole('admin');

    $action = app(ProcessJoinRequest::class);
    $result = $action->handle(-100555444333, 12344321, 'admin_user');

    expect($result)->toBeTrue();
    Http::assertSent(fn ($req) => str_contains($req->url(), 'approveChatJoinRequest'));
});

it('approves join requests for students joining course discussion group', function (): void {
    $course = Course::create([
        'slug' => 'algo',
        'title' => ['ar' => 'خوارزميات', 'en' => 'Algorithms'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_channel_id' => -100555444333,
        'telegram_group_id' => -100999888777,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $student = User::factory()->create(['telegram_user_id' => 11223344]);

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
    ]);

    $action = app(ProcessJoinRequest::class);
    $result = $action->handle(-100999888777, 11223344, 'student_user');

    expect($result)->toBeTrue();
    Http::assertSent(fn ($req) => str_contains($req->url(), 'approveChatJoinRequest'));
});

it('declines join requests for unknown chats', function (): void {
    $student = User::factory()->create(['telegram_user_id' => 11223344]);

    $action = app(ProcessJoinRequest::class);
    $result = $action->handle(-100000000000, 11223344, 'student_user');

    expect($result)->toBeFalse();
    Http::assertSent(fn ($req) => str_contains($req->url(), 'declineChatJoinRequest'));
});

it('marks invite link as used and revokes it on Telegram after approving join request (one-time invite enforcement)', function (): void {
    $course = Course::create([
        'slug' => 'algo',
        'title' => ['ar' => 'خوارزميات', 'en' => 'Algorithms'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_channel_id' => -100555444333,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $student = User::factory()->create(['telegram_user_id' => 11223344]);

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
    ]);

    $invite = TelegramCourseInvite::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'invite_link' => 'https://t.me/+OneTimeApprovalLink',
        'expires_at' => now()->addHours(48),
    ]);

    $action = app(ProcessJoinRequest::class);
    $result = $action->handle(-100555444333, 11223344, 'student_user', 'https://t.me/+OneTimeApprovalLink');

    expect($result)->toBeTrue()
        ->and($invite->fresh()->used_at)->not->toBeNull();

    Http::assertSent(fn ($req) => str_contains($req->url(), 'approveChatJoinRequest')
        && $req['chat_id'] == -100555444333
        && $req['user_id'] === 11223344);
    Http::assertSent(fn ($req) => str_contains($req->url(), 'revokeChatInviteLink')
        && $req['chat_id'] == -100555444333
        && $req['invite_link'] === 'https://t.me/+OneTimeApprovalLink');
});

it('declines join request when invite link was already used or belongs to another student', function (): void {
    $course = Course::create([
        'slug' => 'algo',
        'title' => ['ar' => 'خوارزميات', 'en' => 'Algorithms'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_channel_id' => -100555444333,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $ownerStudent = User::factory()->create(['telegram_user_id' => 11223344]);
    $otherStudent = User::factory()->create(['telegram_user_id' => 99887766]);

    Enrollment::create([
        'user_id' => $otherStudent->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
    ]);

    $invite = TelegramCourseInvite::create([
        'user_id' => $ownerStudent->id,
        'course_id' => $course->id,
        'invite_link' => 'https://t.me/+OwnerOnlyLink',
        'expires_at' => now()->addHours(48),
    ]);

    $action = app(ProcessJoinRequest::class);

    // Other student tries to use ownerStudent's personal invite link
    $resultWrongOwner = $action->handle(-100555444333, 99887766, 'other_student', 'https://t.me/+OwnerOnlyLink');
    expect($resultWrongOwner)->toBeFalse();

    // Mark invite as already used and try again as owner
    $invite->update(['used_at' => now()->subMinute()]);
    $resultAlreadyUsed = $action->handle(-100555444333, 11223344, 'owner_student', 'https://t.me/+OwnerOnlyLink');
    expect($resultAlreadyUsed)->toBeFalse();

    Http::assertSent(fn ($req) => str_contains($req->url(), 'declineChatJoinRequest'));
});
