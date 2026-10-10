<?php

declare(strict_types=1);

use App\Domain\Telegram\Actions\GenerateTelegramLinkToken;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\TelegramCourseInvite;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('telegram.bot_token', 'test_token');
    config()->set('telegram.bot_username', 'TestBot');
    config()->set('telegram.webhook_secret', 'my_secure_secret');
    Http::fake();
});

it('rejects webhook requests without secret token header', function (): void {
    $response = $this->postJson('/api/v1/telegram/webhook', [
        'message' => ['text' => '/start 12345'],
    ]);

    $response->assertStatus(403);
});

it('rejects webhook requests when secret token header is wrong', function (): void {
    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'wrong_secret_token',
    ])->postJson('/api/v1/telegram/webhook', [
        'message' => ['text' => '/start 12345'],
    ]);

    $response->assertStatus(403);
});

it('rejects webhook requests when configured secret is empty string (fail closed)', function (): void {
    config()->set('telegram.webhook_secret', '');

    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'some_token',
    ])->postJson('/api/v1/telegram/webhook', [
        'message' => ['text' => '/start 12345'],
    ]);

    $response->assertStatus(403);

    $responseWithoutHeader = $this->postJson('/api/v1/telegram/webhook', [
        'message' => ['text' => '/start 12345'],
    ]);

    $responseWithoutHeader->assertStatus(403);
});

it('accepts webhook requests with valid secret token header', function (): void {
    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'my_secure_secret',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 1,
    ]);

    $response->assertOk()
        ->assertJson(['ok' => true]);
});

it('handles /start token message in webhook to link user', function (): void {
    $user = User::factory()->create();
    $action = app(GenerateTelegramLinkToken::class);
    $generated = $action->handle($user);

    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'my_secure_secret',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 100,
        'message' => [
            'message_id' => 1,
            'from' => [
                'id' => 555666777,
                'username' => 'azhar_student',
            ],
            'chat' => [
                'id' => 555666777,
                'type' => 'private',
            ],
            'text' => "/start {$generated['token']}",
        ],
    ]);

    $response->assertOk();

    $user->refresh();
    expect($user->telegram_user_id)->toBe(555666777)
        ->and($user->telegram_username)->toBe('azhar_student');
});

it('handles chat_join_request in webhook and approves eligible students', function (): void {
    $course = Course::create([
        'slug' => 'cs101',
        'title' => ['ar' => 'برمجة', 'en' => 'Programming'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 10000,
        'telegram_channel_id' => -1001987654321,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل 1', 'en' => 'Term 1'],
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $student = User::factory()->create([
        'telegram_user_id' => 777888999,
        'telegram_username' => 'eligible_student',
    ]);

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
    ]);

    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'my_secure_secret',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 200,
        'chat_join_request' => [
            'chat' => [
                'id' => -1001987654321,
                'title' => 'CS101 Group',
            ],
            'from' => [
                'id' => 777888999,
                'username' => 'eligible_student',
            ],
            'date' => time(),
        ],
    ]);

    $response->assertOk();

    Http::assertSent(fn ($req) => str_contains($req->url(), 'approveChatJoinRequest')
        && $req['chat_id'] == -1001987654321
        && $req['user_id'] === 777888999);
});

it('handles chat_join_request for group in webhook and approves eligible students', function (): void {
    $course = Course::create([
        'slug' => 'cs102',
        'title' => ['ar' => 'برمجة 2', 'en' => 'Programming 2'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 10000,
        'telegram_channel_id' => -1001111111111,
        'telegram_group_id' => -1002222222222,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل 1', 'en' => 'Term 1'],
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $student = User::factory()->create([
        'telegram_user_id' => 888999111,
        'telegram_username' => 'group_student',
    ]);

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
    ]);

    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'my_secure_secret',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 201,
        'chat_join_request' => [
            'chat' => [
                'id' => -1002222222222,
                'title' => 'CS102 Discussion Group',
            ],
            'from' => [
                'id' => 888999111,
                'username' => 'group_student',
            ],
            'date' => time(),
        ],
    ]);

    $response->assertOk();

    Http::assertSent(fn ($req) => str_contains($req->url(), 'approveChatJoinRequest')
        && $req['chat_id'] == -1002222222222
        && $req['user_id'] === 888999111);
});

it('catches internal errors in webhook handler and still returns 200 ok', function (): void {
    // Malformed/unexpected payload that causes an action to fail or throw
    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'my_secure_secret',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 999,
        'message' => [
            'from' => ['id' => 12345],
            'text' => '/start validtoken',
        ],
    ]);

    // Should still return HTTP 200 with ok: true
    $response->assertOk()->assertJsonPath('ok', true);
});

it('marks invite as used when chat_member update indicates user became member', function (): void {
    $course = Course::create([
        'slug' => 'course-chat-member',
        'title' => ['ar' => 'دورة', 'en' => 'Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
        'status' => 'published',
        'telegram_group_id' => -100999888,
    ]);

    $student = User::factory()->create([
        'telegram_user_id' => 777888999,
    ]);

    $invite = TelegramCourseInvite::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'invite_link' => 'https://t.me/+joinlink',
        'expires_at' => now()->addDays(2),
    ]);

    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'my_secure_secret',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 301,
        'chat_member' => [
            'chat' => ['id' => -100999888],
            'from' => ['id' => 777888999],
            'new_chat_member' => [
                'status' => 'member',
                'user' => ['id' => 777888999],
            ],
            'invite_link' => [
                'invite_link' => 'https://t.me/+joinlink',
            ],
        ],
    ]);

    $response->assertOk()->assertJsonPath('ok', true);
    expect($invite->fresh()->used_at)->not->toBeNull();
});

it('marks only the matching invite_link row for that course', function (): void {
    $courseA = Course::create([
        'slug' => 'course-scope-a',
        'title' => ['ar' => 'دورة أ', 'en' => 'Course A'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
        'status' => 'published',
        'telegram_group_id' => -100111222,
    ]);

    $courseB = Course::create([
        'slug' => 'course-scope-b',
        'title' => ['ar' => 'دورة ب', 'en' => 'Course B'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
        'status' => 'published',
        'telegram_group_id' => -100333444,
    ]);

    $student1 = User::factory()->create(['telegram_user_id' => 11111]);
    $student2 = User::factory()->create(['telegram_user_id' => 22222]);

    $inviteA1 = TelegramCourseInvite::create([
        'user_id' => $student1->id,
        'course_id' => $courseA->id,
        'invite_link' => 'https://t.me/+linkA1',
        'expires_at' => now()->addDays(2),
    ]);

    $inviteA2 = TelegramCourseInvite::create([
        'user_id' => $student1->id,
        'course_id' => $courseA->id,
        'invite_link' => 'https://t.me/+linkA2',
        'expires_at' => now()->addDays(2),
    ]);

    $inviteStudent2 = TelegramCourseInvite::create([
        'user_id' => $student2->id,
        'course_id' => $courseA->id,
        'invite_link' => 'https://t.me/+linkStudent2',
        'expires_at' => now()->addDays(2),
    ]);

    $inviteB1 = TelegramCourseInvite::create([
        'user_id' => $student1->id,
        'course_id' => $courseB->id,
        'invite_link' => 'https://t.me/+linkB1',
        'expires_at' => now()->addDays(2),
    ]);

    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'my_secure_secret',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 302,
        'chat_member' => [
            'chat' => ['id' => -100111222],
            'from' => ['id' => 11111],
            'new_chat_member' => [
                'status' => 'member',
                'user' => ['id' => 11111],
            ],
            'invite_link' => [
                'invite_link' => 'https://t.me/+linkA1',
            ],
        ],
    ]);

    $response->assertOk()->assertJsonPath('ok', true);

    expect($inviteA1->fresh()->used_at)->not->toBeNull()
        ->and($inviteA2->fresh()->used_at)->toBeNull()
        ->and($inviteStudent2->fresh()->used_at)->toBeNull()
        ->and($inviteB1->fresh()->used_at)->toBeNull();
});

it('fallback without invite_link marks only the specific user and course pair', function (): void {
    $courseA = Course::create([
        'slug' => 'course-fallback-a',
        'title' => ['ar' => 'دورة أ', 'en' => 'Course A'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
        'status' => 'published',
        'telegram_group_id' => -100555666,
    ]);

    $courseB = Course::create([
        'slug' => 'course-fallback-b',
        'title' => ['ar' => 'دورة ب', 'en' => 'Course B'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
        'status' => 'published',
        'telegram_group_id' => -100777888,
    ]);

    $student1 = User::factory()->create(['telegram_user_id' => 33333]);
    $student2 = User::factory()->create(['telegram_user_id' => 44444]);

    $inviteAStudent1 = TelegramCourseInvite::create([
        'user_id' => $student1->id,
        'course_id' => $courseA->id,
        'invite_link' => 'https://t.me/+fallbackA1',
        'expires_at' => now()->addDays(2),
    ]);

    $inviteBStudent1 = TelegramCourseInvite::create([
        'user_id' => $student1->id,
        'course_id' => $courseB->id,
        'invite_link' => 'https://t.me/+fallbackB1',
        'expires_at' => now()->addDays(2),
    ]);

    $inviteAStudent2 = TelegramCourseInvite::create([
        'user_id' => $student2->id,
        'course_id' => $courseA->id,
        'invite_link' => 'https://t.me/+fallbackA2',
        'expires_at' => now()->addDays(2),
    ]);

    // chat_member payload with no invite_link
    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'my_secure_secret',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 303,
        'chat_member' => [
            'chat' => ['id' => -100555666],
            'from' => ['id' => 33333],
            'new_chat_member' => [
                'status' => 'member',
                'user' => ['id' => 33333],
            ],
        ],
    ]);

    $response->assertOk()->assertJsonPath('ok', true);

    expect($inviteAStudent1->fresh()->used_at)->not->toBeNull()
        ->and($inviteBStudent1->fresh()->used_at)->toBeNull()
        ->and($inviteAStudent2->fresh()->used_at)->toBeNull();
});

it('chat_member update for another chat does not touch any invites', function (): void {
    $course = Course::create([
        'slug' => 'course-unrelated',
        'title' => ['ar' => 'دورة', 'en' => 'Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
        'status' => 'published',
        'telegram_group_id' => -100111222,
    ]);

    $student = User::factory()->create(['telegram_user_id' => 55555]);

    $invite = TelegramCourseInvite::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'invite_link' => 'https://t.me/+unrelatedLink',
        'expires_at' => now()->addDays(2),
    ]);

    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'my_secure_secret',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 304,
        'chat_member' => [
            'chat' => ['id' => -100999999], // unrelated chat ID
            'from' => ['id' => 55555],
            'new_chat_member' => [
                'status' => 'member',
                'user' => ['id' => 55555],
            ],
            'invite_link' => [
                'invite_link' => 'https://t.me/+unrelatedLink',
            ],
        ],
    ]);

    $response->assertOk()->assertJsonPath('ok', true);
    expect($invite->fresh()->used_at)->toBeNull();
});

it('always returns 200 ok even on unexpected or malformed chat_member updates', function (): void {
    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'my_secure_secret',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 305,
        'chat_member' => [
            'chat' => null,
            'new_chat_member' => [
                'status' => 'left',
            ],
        ],
    ]);

    $response->assertOk()->assertJsonPath('ok', true);
});

it('sends instructions when unlinked user sends start without token', function (): void {
    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'my_secure_secret',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 306,
        'message' => [
            'message_id' => 10,
            'from' => [
                'id' => 998877,
                'username' => 'new_student',
            ],
            'chat' => [
                'id' => 998877,
                'type' => 'private',
            ],
            'text' => 'Start',
        ],
    ]);

    $response->assertOk()->assertJsonPath('ok', true);
    Http::assertSent(fn ($req) => str_contains($req->url(), 'sendMessage')
        && $req['chat_id'] === 998877
        && str_contains($req['text'], 'مرحباً بك في بوت منصة كوديرا'));
});

it('informs user when already linked user sends start or greeting', function (): void {
    $student = User::factory()->create([
        'name' => 'Ahmed Ali',
        'telegram_user_id' => 11223344,
    ]);

    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'my_secure_secret',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 307,
        'message' => [
            'message_id' => 11,
            'from' => [
                'id' => 11223344,
                'username' => 'ahmed_ali',
            ],
            'chat' => [
                'id' => 11223344,
                'type' => 'private',
            ],
            'text' => '/start',
        ],
    ]);

    $response->assertOk()->assertJsonPath('ok', true);
    Http::assertSent(fn ($req) => str_contains($req->url(), 'sendMessage')
        && $req['chat_id'] === 11223344
        && str_contains($req['text'], 'مرتبط بالفعل بمنصة Codeera'));
});

