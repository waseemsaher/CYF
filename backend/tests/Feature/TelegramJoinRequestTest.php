<?php

declare(strict_types=1);

use App\Domain\Telegram\Actions\ProcessJoinRequest;
use App\Models\Course;
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

it('approves join requests for students with active valid enrollments', function (): void {
    $course = Course::create([
        'slug' => 'algo',
        'title' => ['ar' => 'خوارزميات', 'en' => 'Algorithms'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_chat_id' => -100555444333,
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
        'telegram_chat_id' => -100555444333,
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
        'telegram_chat_id' => -100555444333,
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
        'telegram_chat_id' => -100555444333,
    ]);

    $admin = User::factory()->create(['telegram_user_id' => 12344321]);
    $admin->assignRole('admin');

    $action = app(ProcessJoinRequest::class);
    $result = $action->handle(-100555444333, 12344321, 'admin_user');

    expect($result)->toBeTrue();
    Http::assertSent(fn ($req) => str_contains($req->url(), 'approveChatJoinRequest'));
});
