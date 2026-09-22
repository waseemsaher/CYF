<?php

declare(strict_types=1);

use App\Domain\Telegram\Actions\GenerateTelegramLinkToken;
use App\Models\Course;
use App\Models\Enrollment;
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

it('rejects webhook requests without valid secret token header', function (): void {
    $response = $this->postJson('/api/v1/telegram/webhook', [
        'message' => ['text' => '/start 12345'],
    ]);

    $response->assertStatus(403);
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
        'telegram_chat_id' => -1001987654321,
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
