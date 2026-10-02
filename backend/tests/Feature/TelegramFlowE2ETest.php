<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('telegram.bot_token', 'test_secret_bot_token_123');
    config()->set('telegram.bot_username', 'Codeera_bot');
    config()->set('telegram.webhook_secret', 'test_webhook_secret_xyz');
    Http::fake();
    Role::findOrCreate('student');
});

it('links account via real token generation and /start webhook', function (): void {
    $user = User::factory()->create([
        'name' => 'Tariq Student',
        'email' => 'tariq@example.com',
        'telegram_user_id' => null,
        'telegram_username' => null,
    ]);
    $user->assignRole('student');

    // 1. Generate link token via POST /api/v1/telegram/link-token
    Sanctum::actingAs($user, ['*']);
    $tokenResponse = $this->postJson('/api/v1/telegram/link-token');
    $tokenResponse->assertOk()
        ->assertJsonStructure([
            'data' => ['token', 'deep_link', 'expires_at'],
        ]);

    $plainToken = $tokenResponse->json('data.token');
    expect($plainToken)->toBeString()->not->toBeEmpty();
    expect($tokenResponse->json('data.deep_link'))->toBe("https://t.me/Codeera_bot?start={$plainToken}");

    // 2. Simulate Telegram webhook delivering /start <token>
    $telegramUserId = 777123456;
    $telegramUsername = 'tariq_telegram';

    $linkWebhookResponse = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'test_webhook_secret_xyz',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 10001,
        'message' => [
            'message_id' => 1,
            'from' => [
                'id' => $telegramUserId,
                'is_bot' => false,
                'first_name' => 'Tariq',
                'username' => $telegramUsername,
            ],
            'chat' => [
                'id' => $telegramUserId,
                'type' => 'private',
            ],
            'date' => time(),
            'text' => "/start {$plainToken}",
        ],
    ]);

    $linkWebhookResponse->assertOk()->assertJson(['ok' => true]);

    // Verify user is linked in the database
    $user->refresh();
    expect($user->telegram_user_id)->toBe($telegramUserId)
        ->and($user->telegram_username)->toBe($telegramUsername);

    // Verify welcome message was sent
    Http::assertSent(fn ($req) => str_contains($req->url(), 'sendMessage') && $req['chat_id'] === $telegramUserId);
});

it('Case A: approves join request for student with active course enrollment', function (): void {
    $course = Course::create([
        'slug' => 'course-alpha',
        'title' => ['ar' => 'مقرر أ', 'en' => 'Course Alpha'],
        'description' => ['ar' => 'وصف أ', 'en' => 'Desc Alpha'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_group_id' => -1001234567890,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل 1', 'en' => 'Term 1'],
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $student = User::factory()->create([
        'telegram_user_id' => 777123456,
        'telegram_username' => 'tariq_telegram',
    ]);
    $student->assignRole('student');

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
        'X-Telegram-Bot-Api-Secret-Token' => 'test_webhook_secret_xyz',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 10002,
        'chat_join_request' => [
            'chat' => [
                'id' => -1001234567890,
                'title' => 'Course Alpha Group',
                'type' => 'supergroup',
            ],
            'from' => [
                'id' => 777123456,
                'is_bot' => false,
                'first_name' => 'Tariq',
                'username' => 'tariq_telegram',
            ],
            'user_chat_id' => 777123456,
            'date' => time(),
        ],
    ]);

    $response->assertOk()->assertJson(['ok' => true]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'approveChatJoinRequest')
            && (int) $request['chat_id'] === -1001234567890
            && (int) $request['user_id'] === 777123456;
    });
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'declineChatJoinRequest'));
});

it('Case B: declines join request when enrollment is expired', function (): void {
    $course = Course::create([
        'slug' => 'course-alpha',
        'title' => ['ar' => 'مقرر أ', 'en' => 'Course Alpha'],
        'description' => ['ar' => 'وصف أ', 'en' => 'Desc Alpha'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_group_id' => -1001234567890,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل سابق', 'en' => 'Past Term'],
        'starts_at' => now()->subMonths(3),
        'ends_at' => now()->subDay(),
        'is_current' => false,
    ]);

    $student = User::factory()->create([
        'telegram_user_id' => 777123456,
        'telegram_username' => 'tariq_telegram',
    ]);
    $student->assignRole('student');

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subMonths(3),
        'expires_at' => now()->subDay(),
    ]);

    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'test_webhook_secret_xyz',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 10003,
        'chat_join_request' => [
            'chat' => [
                'id' => -1001234567890,
                'title' => 'Course Alpha Group',
                'type' => 'supergroup',
            ],
            'from' => [
                'id' => 777123456,
                'is_bot' => false,
                'first_name' => 'Tariq',
                'username' => 'tariq_telegram',
            ],
            'user_chat_id' => 777123456,
            'date' => time(),
        ],
    ]);

    $response->assertOk()->assertJson(['ok' => true]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'declineChatJoinRequest')
            && (int) $request['chat_id'] === -1001234567890
            && (int) $request['user_id'] === 777123456;
    });
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'approveChatJoinRequest'));
});

it('Case C: declines join request for different course with no enrollment (no cross-course leakage)', function (): void {
    $courseAlpha = Course::create([
        'slug' => 'course-alpha',
        'title' => ['ar' => 'مقرر أ', 'en' => 'Course Alpha'],
        'description' => ['ar' => 'وصف أ', 'en' => 'Desc Alpha'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_group_id' => -1001234567890,
    ]);

    $courseBeta = Course::create([
        'slug' => 'course-beta',
        'title' => ['ar' => 'مقرر ب', 'en' => 'Course Beta'],
        'description' => ['ar' => 'وصف ب', 'en' => 'Desc Beta'],
        'status' => 'published',
        'price_cents' => 20000,
        'telegram_group_id' => -1009876543210,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل 1', 'en' => 'Term 1'],
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);

    $student = User::factory()->create([
        'telegram_user_id' => 777123456,
        'telegram_username' => 'tariq_telegram',
    ]);
    $student->assignRole('student');

    // Student is only enrolled in Course Alpha
    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $courseAlpha->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
    ]);

    // Student tries to join Course Beta group
    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'test_webhook_secret_xyz',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 10004,
        'chat_join_request' => [
            'chat' => [
                'id' => -1009876543210,
                'title' => 'Course Beta Group',
                'type' => 'supergroup',
            ],
            'from' => [
                'id' => 777123456,
                'is_bot' => false,
                'first_name' => 'Tariq',
                'username' => 'tariq_telegram',
            ],
            'user_chat_id' => 777123456,
            'date' => time(),
        ],
    ]);

    $response->assertOk()->assertJson(['ok' => true]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'declineChatJoinRequest')
            && (int) $request['chat_id'] === -1009876543210
            && (int) $request['user_id'] === 777123456;
    });
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'approveChatJoinRequest'));
});

it('Case D: declines join request for unlinked Telegram user ID', function (): void {
    Course::create([
        'slug' => 'course-alpha',
        'title' => ['ar' => 'مقرر أ', 'en' => 'Course Alpha'],
        'description' => ['ar' => 'وصف أ', 'en' => 'Desc Alpha'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_group_id' => -1001234567890,
    ]);

    $unlinkedUserId = 999111222;

    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'test_webhook_secret_xyz',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 10005,
        'chat_join_request' => [
            'chat' => [
                'id' => -1001234567890,
                'title' => 'Course Alpha Group',
                'type' => 'supergroup',
            ],
            'from' => [
                'id' => $unlinkedUserId,
                'is_bot' => false,
                'first_name' => 'Stranger',
                'username' => 'stranger_user',
            ],
            'user_chat_id' => $unlinkedUserId,
            'date' => time(),
        ],
    ]);

    $response->assertOk()->assertJson(['ok' => true]);

    Http::assertSent(function ($request) use ($unlinkedUserId) {
        return str_contains($request->url(), 'declineChatJoinRequest')
            && (int) $request['chat_id'] === -1001234567890
            && (int) $request['user_id'] === $unlinkedUserId;
    });
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'approveChatJoinRequest'));
});

it('handles other update types gracefully without erroring', function (): void {
    $response = $this->withHeaders([
        'X-Telegram-Bot-Api-Secret-Token' => 'test_webhook_secret_xyz',
    ])->postJson('/api/v1/telegram/webhook', [
        'update_id' => 10006,
        'my_chat_member' => [
            'chat' => ['id' => -1001234567890, 'title' => 'Alpha Group'],
            'from' => ['id' => 12345, 'is_bot' => false],
            'date' => time(),
            'old_chat_member' => ['status' => 'member'],
            'new_chat_member' => ['status' => 'administrator'],
        ],
    ]);

    $response->assertOk()->assertJson(['ok' => true]);
});

it('removes expired members via artisan telegram:remove-expired', function (): void {
    $course = Course::create([
        'slug' => 'course-alpha',
        'title' => ['ar' => 'مقرر أ', 'en' => 'Course Alpha'],
        'description' => ['ar' => 'وصف أ', 'en' => 'Desc Alpha'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_group_id' => -1001234567890,
    ]);

    $term = Term::create([
        'name' => ['ar' => 'فصل 1', 'en' => 'Term 1'],
        'starts_at' => now()->subMonths(3),
        'ends_at' => now()->subDay(),
        'is_current' => false,
    ]);

    $student = User::factory()->create([
        'telegram_user_id' => 888777999,
        'telegram_username' => 'expired_student',
    ]);

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'term_id' => $term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subMonths(3),
        'expires_at' => now()->subDay(),
    ]);

    Artisan::call('telegram:remove-expired');

    Http::assertSent(fn ($req) => str_contains($req->url(), 'banChatMember') && (int) $req['user_id'] === 888777999);
    Http::assertSent(fn ($req) => str_contains($req->url(), 'unbanChatMember') && (int) $req['user_id'] === 888777999);
});
