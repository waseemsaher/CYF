<?php

declare(strict_types=1);

use App\Domain\Telegram\Actions\SendCourseInviteLink;
use App\Jobs\SendCourseInviteLinkJob;
use App\Jobs\SendTelegramNotificationJob;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\TelegramCourseInvite;
use App\Models\TelegramLinkToken;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// ─── Shared helpers ──────────────────────────────────────────────────────────

function inviteTestSetup(): array
{
    Permission::findOrCreate('payments.review', 'web');
    Permission::findOrCreate('courses.manage', 'web');
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::findOrCreate('superadmin', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo('payments.review');
    Role::findOrCreate('student', 'web');

    Setting::setValue('revenue', 'default_teacher_share_percent', 70);
    Setting::setValue('enrollment', 'grace_days', 0);

    config()->set('telegram.webhook_secret', 'test_secret');
    config()->set('telegram.bot_token', 'test-bot-token');
    config()->set('telegram.api_url', 'https://api.telegram.org');
    config()->set('telegram.bot_username', 'Codeera_bot');

    $student = User::factory()->create(['email_verified_at' => now()]);
    $student->assignRole('student');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $courseWithGroup = Course::create([
        'slug' => 'course-with-group',
        'title' => ['ar' => 'دورة مع مجموعة', 'en' => 'Course With Group'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
        'status' => 'published',
        'telegram_group_id' => -1001234567890,
    ]);

    $courseWithoutGroup = Course::create([
        'slug' => 'course-no-group',
        'title' => ['ar' => 'دورة بدون مجموعة', 'en' => 'Course Without Group'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
        'status' => 'published',
        // No telegram_group_id
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل', 'en' => 'Term'],
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addMonths(3),
        'is_current' => true,
        'sort_order' => 1,
    ]);

    return compact('student', 'admin', 'courseWithGroup', 'courseWithoutGroup', 'term');
}

function makePayment(User $student, Course $course, Term $term): Payment
{
    return Payment::create([
        'user_id' => $student->getKey(),
        'course_id' => $course->getKey(),
        'term_id' => $term->getKey(),
        'method' => 'vodafone_cash',
        'list_price_cents' => 10000,
        'discount_cents' => 0,
        'amount_due_cents' => 10000,
        'sender_identifier' => '01012345678',
        'proof_path' => 'proofs/test.jpg',
        'proof_hash' => hash('sha256', uniqid()),
        'status' => 'pending',
    ]);
}

// ─── Trigger A Tests (payment approval) ──────────────────────────────────────

it('dispatches SendCourseInviteLinkJob when payment approved and student is linked + course has group', function (): void {
    Queue::fake();
    $d = inviteTestSetup();

    // Link the student's Telegram account
    $d['student']->update(['telegram_user_id' => 111222333]);

    $payment = makePayment($d['student'], $d['courseWithGroup'], $d['term']);

    $this->actingAs($d['admin'], 'sanctum')
        ->postJson('/api/v1/admin/payments/'.$payment->getKey().'/approve')
        ->assertOk();

    Queue::assertPushed(SendCourseInviteLinkJob::class, function (SendCourseInviteLinkJob $job) use ($d): bool {
        return $job->user->getKey() === $d['student']->getKey()
            && $job->course->getKey() === $d['courseWithGroup']->getKey();
    });
    // Should NOT dispatch the plain notification job for courses with groups
    Queue::assertNotPushed(SendTelegramNotificationJob::class);
});

it('falls back to SendTelegramNotificationJob when course has no group_id', function (): void {
    Queue::fake();
    $d = inviteTestSetup();

    $d['student']->update(['telegram_user_id' => 111222333]);
    $payment = makePayment($d['student'], $d['courseWithoutGroup'], $d['term']);

    $this->actingAs($d['admin'], 'sanctum')
        ->postJson('/api/v1/admin/payments/'.$payment->getKey().'/approve')
        ->assertOk();

    Queue::assertNotPushed(SendCourseInviteLinkJob::class);
    Queue::assertPushed(SendTelegramNotificationJob::class, function (SendTelegramNotificationJob $job): bool {
        return $job->telegramUserId === 111222333;
    });
});

it('dispatches nothing when student has no linked Telegram on approval', function (): void {
    Queue::fake();
    $d = inviteTestSetup();

    // Student NOT linked
    $payment = makePayment($d['student'], $d['courseWithGroup'], $d['term']);

    $this->actingAs($d['admin'], 'sanctum')
        ->postJson('/api/v1/admin/payments/'.$payment->getKey().'/approve')
        ->assertOk();

    Queue::assertNotPushed(SendCourseInviteLinkJob::class);
    Queue::assertNotPushed(SendTelegramNotificationJob::class);
});

// ─── SendCourseInviteLink Action Tests (direct, HTTP-faked) ──────────────────

it('sends createChatInviteLink + sendMessage when no outstanding invite exists (Trigger A integration)', function (): void {
    $d = inviteTestSetup();
    $d['student']->update(['telegram_user_id' => 111222333]);

    Http::fake([
        'https://api.telegram.org/bot*/createChatInviteLink' => Http::response([
            'ok' => true,
            'result' => [
                'invite_link' => 'https://t.me/+AbCdEfGhIjK',
                'member_limit' => 1,
                'expire_date' => now()->addHours(48)->timestamp,
            ],
        ]),
        'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true]),
    ]);

    $action = app(SendCourseInviteLink::class);
    $result = $action->handle($d['student'], $d['courseWithGroup']);

    expect($result)->toBeTrue();

    // Invite link was persisted
    $this->assertDatabaseHas('telegram_course_invites', [
        'user_id' => $d['student']->getKey(),
        'course_id' => $d['courseWithGroup']->getKey(),
        'invite_link' => 'https://t.me/+AbCdEfGhIjK',
    ]);

    Http::assertSentCount(3); // unbanChatMember + createChatInviteLink + sendMessage
    Http::assertSent(fn ($req) => str_contains($req->url(), 'unbanChatMember')
        && $req['chat_id'] == $d['courseWithGroup']->telegram_group_id
        && $req['user_id'] === 111222333
        && $req['only_if_banned'] === true);
    Http::assertSent(fn ($req) => str_contains($req->url(), 'createChatInviteLink')
        && $req['member_limit'] === 1
        && $req['creates_join_request'] === false);
    Http::assertSent(fn ($req) => str_contains($req->url(), 'sendMessage')
        && str_contains($req['text'], 'https://t.me/+AbCdEfGhIjK'));
});

it('skips API call when an outstanding invite already exists (idempotency)', function (): void {
    $d = inviteTestSetup();
    $d['student']->update(['telegram_user_id' => 111222333]);

    // Pre-insert an existing outstanding invite
    TelegramCourseInvite::create([
        'user_id' => $d['student']->getKey(),
        'course_id' => $d['courseWithGroup']->getKey(),
        'invite_link' => 'https://t.me/+ExistingLink',
        'expires_at' => now()->addHours(24), // still valid
    ]);

    Http::fake(); // should receive zero calls

    $action = app(SendCourseInviteLink::class);
    $result = $action->handle($d['student'], $d['courseWithGroup']);

    expect($result)->toBeFalse();
    Http::assertSentCount(0);
});

it('sends a new invite when a previous invite has expired', function (): void {
    $d = inviteTestSetup();
    $d['student']->update(['telegram_user_id' => 111222333]);

    // Pre-insert an EXPIRED invite
    TelegramCourseInvite::create([
        'user_id' => $d['student']->getKey(),
        'course_id' => $d['courseWithGroup']->getKey(),
        'invite_link' => 'https://t.me/+OldExpiredLink',
        'expires_at' => now()->subHour(), // already expired
    ]);

    Http::fake([
        'https://api.telegram.org/bot*/createChatInviteLink' => Http::response([
            'ok' => true,
            'result' => ['invite_link' => 'https://t.me/+NewFreshLink', 'member_limit' => 1],
        ]),
        'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true]),
    ]);

    $action = app(SendCourseInviteLink::class);
    $result = $action->handle($d['student'], $d['courseWithGroup']);

    expect($result)->toBeTrue();
    $this->assertDatabaseHas('telegram_course_invites', [
        'user_id' => $d['student']->getKey(),
        'course_id' => $d['courseWithGroup']->getKey(),
        'invite_link' => 'https://t.me/+NewFreshLink',
    ]);
});

it('does nothing when course has no telegram_group_id', function (): void {
    $d = inviteTestSetup();
    $d['student']->update(['telegram_user_id' => 111222333]);

    Http::fake();

    $action = app(SendCourseInviteLink::class);
    $result = $action->handle($d['student'], $d['courseWithoutGroup']);

    expect($result)->toBeFalse();
    Http::assertSentCount(0);
});

it('does nothing when user has no telegram_user_id', function (): void {
    $d = inviteTestSetup();
    // student NOT linked

    Http::fake();

    $action = app(SendCourseInviteLink::class);
    $result = $action->handle($d['student'], $d['courseWithGroup']);

    expect($result)->toBeFalse();
    Http::assertSentCount(0);
});

// ─── Trigger B Tests (linking after active enrollment) ───────────────────────

it('sends invite links for all active enrollments when Telegram is linked (Trigger B)', function (): void {
    $d = inviteTestSetup();

    // Create a second course with a group
    $courseWithGroup2 = Course::create([
        'slug' => 'course-with-group-2',
        'title' => ['ar' => 'دورة 2', 'en' => 'Course 2'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
        'status' => 'published',
        'telegram_group_id' => -1009876543210,
    ]);

    // Pre-create two active enrollments for the student
    Enrollment::create([
        'user_id' => $d['student']->getKey(),
        'course_id' => $d['courseWithGroup']->getKey(),
        'term_id' => $d['term']->getKey(),
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonths(3),
    ]);

    Enrollment::create([
        'user_id' => $d['student']->getKey(),
        'course_id' => $courseWithGroup2->getKey(),
        'term_id' => $d['term']->getKey(),
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonths(3),
    ]);

    Http::fake([
        'https://api.telegram.org/bot*/createChatInviteLink' => Http::sequence()
            ->push(['ok' => true, 'result' => ['invite_link' => 'https://t.me/+Link1', 'member_limit' => 1]])
            ->push(['ok' => true, 'result' => ['invite_link' => 'https://t.me/+Link2', 'member_limit' => 1]]),
        'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true]),
    ]);

    // Generate a link token and exercise the webhook
    $plainToken = bin2hex(random_bytes(32));
    TelegramLinkToken::create([
        'user_id' => $d['student']->getKey(),
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->postJson('/api/v1/telegram/webhook', [
        'update_id' => 99001,
        'message' => [
            'message_id' => 1,
            'from' => ['id' => 555666777, 'is_bot' => false, 'first_name' => 'Ali'],
            'chat' => ['id' => 555666777, 'type' => 'private'],
            'date' => now()->timestamp,
            'text' => "/start {$plainToken}",
        ],
    ], ['X-Telegram-Bot-Api-Secret-Token' => 'test_secret']);

    $response->assertOk()->assertJsonPath('ok', true);

    // User should now be linked
    $this->assertDatabaseHas('users', [
        'id' => $d['student']->getKey(),
        'telegram_user_id' => 555666777,
    ]);

    // Two invite links should have been created (one per course)
    $this->assertDatabaseCount('telegram_course_invites', 2);
    $this->assertDatabaseHas('telegram_course_invites', [
        'user_id' => $d['student']->getKey(),
        'course_id' => $d['courseWithGroup']->getKey(),
        'invite_link' => 'https://t.me/+Link1',
    ]);
    $this->assertDatabaseHas('telegram_course_invites', [
        'user_id' => $d['student']->getKey(),
        'course_id' => $courseWithGroup2->getKey(),
        'invite_link' => 'https://t.me/+Link2',
    ]);

    // 1 welcome sendMessage + 2 unbanChatMember + 2 createChatInviteLink + 2 invite sendMessage = 7 HTTP calls
    Http::assertSentCount(7);
});

it('does not persist invite row if sendMessage delivery fails allowing future retries', function (): void {
    $d = inviteTestSetup();
    $d['student']->update(['telegram_user_id' => 111222333]);

    Http::fake([
        'https://api.telegram.org/bot*/unbanChatMember' => Http::response(['ok' => true]),
        'https://api.telegram.org/bot*/createChatInviteLink' => Http::response([
            'ok' => true,
            'result' => [
                'invite_link' => 'https://t.me/+AbCdEfGhIjK',
                'member_limit' => 1,
                'expire_date' => now()->addHours(48)->timestamp,
            ],
        ]),
        'https://api.telegram.org/bot*/sendMessage' => Http::response([
            'ok' => false,
            'error_code' => 403,
            'description' => 'Forbidden: bot was blocked by the user',
        ], 403),
    ]);

    $action = app(SendCourseInviteLink::class);

    try {
        $action->handle($d['student'], $d['courseWithGroup']);
    } catch (Throwable $e) {
        // Exception caught
    }

    $this->assertDatabaseMissing('telegram_course_invites', [
        'user_id' => $d['student']->getKey(),
        'course_id' => $d['courseWithGroup']->getKey(),
    ]);
});

it('does not send a second invite link when linking Telegram again after already having one outstanding', function (): void {
    $d = inviteTestSetup();

    // Active enrollment
    Enrollment::create([
        'user_id' => $d['student']->getKey(),
        'course_id' => $d['courseWithGroup']->getKey(),
        'term_id' => $d['term']->getKey(),
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonths(3),
    ]);

    // Outstanding invite already sent
    TelegramCourseInvite::create([
        'user_id' => $d['student']->getKey(),
        'course_id' => $d['courseWithGroup']->getKey(),
        'invite_link' => 'https://t.me/+AlreadySent',
        'expires_at' => now()->addHours(24),
    ]);

    Http::fake([
        'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true]),
        // createChatInviteLink should NOT be called
    ]);

    $plainToken = bin2hex(random_bytes(32));
    TelegramLinkToken::create([
        'user_id' => $d['student']->getKey(),
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addMinutes(15),
    ]);

    $this->postJson('/api/v1/telegram/webhook', [
        'update_id' => 99002,
        'message' => [
            'message_id' => 2,
            'from' => ['id' => 555666777, 'is_bot' => false, 'first_name' => 'Ali'],
            'chat' => ['id' => 555666777, 'type' => 'private'],
            'date' => now()->timestamp,
            'text' => "/start {$plainToken}",
        ],
    ], ['X-Telegram-Bot-Api-Secret-Token' => 'test_secret'])->assertOk();

    // Only the welcome sendMessage — no createChatInviteLink
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'createChatInviteLink'));
    // The existing invite record should still be there, unchanged
    $this->assertDatabaseCount('telegram_course_invites', 1);
});

it('skips expired enrollments in Trigger B', function (): void {
    $d = inviteTestSetup();

    // Expired enrollment
    Enrollment::create([
        'user_id' => $d['student']->getKey(),
        'course_id' => $d['courseWithGroup']->getKey(),
        'term_id' => $d['term']->getKey(),
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDays(60),
        'expires_at' => now()->subDays(1), // already past
    ]);

    Http::fake([
        'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true]),
    ]);

    $plainToken = bin2hex(random_bytes(32));
    TelegramLinkToken::create([
        'user_id' => $d['student']->getKey(),
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addMinutes(15),
    ]);

    $this->postJson('/api/v1/telegram/webhook', [
        'update_id' => 99003,
        'message' => [
            'message_id' => 3,
            'from' => ['id' => 555666777, 'is_bot' => false, 'first_name' => 'Ali'],
            'chat' => ['id' => 555666777, 'type' => 'private'],
            'date' => now()->timestamp,
            'text' => "/start {$plainToken}",
        ],
    ], ['X-Telegram-Bot-Api-Secret-Token' => 'test_secret'])->assertOk();

    // No invite should be created for expired enrollments
    $this->assertDatabaseCount('telegram_course_invites', 0);
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'createChatInviteLink'));
});
