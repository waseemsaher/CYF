<?php

declare(strict_types=1);

use App\Domain\Telegram\Actions\GenerateTelegramLinkToken;
use App\Domain\Telegram\Actions\LinkTelegramUser;
use App\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('telegram.bot_token', 'test_token');
    config()->set('telegram.bot_username', 'TestBot');
    config()->set('telegram.webhook_secret', 'test_secret');
    Http::fake();
});

it('allows an authenticated user to generate a telegram link token and deep link', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/telegram/link-token');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'token',
                'deep_link',
                'expires_at',
            ],
        ]);

    $data = $response->json('data');
    expect($data['deep_link'])->toContain('https://t.me/TestBot?start=')
        ->and(TelegramLinkToken::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('invalidates prior unused tokens when a new link token is generated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/v1/telegram/link-token')->assertOk();
    $this->actingAs($user)->postJson('/api/v1/telegram/link-token')->assertOk();

    expect(TelegramLinkToken::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('links telegram account on valid /start token', function (): void {
    $user = User::factory()->create();
    $action = app(GenerateTelegramLinkToken::class);
    $generated = $action->handle($user);

    $linkAction = app(LinkTelegramUser::class);
    $success = $linkAction->handle(987654321, 'test_student', $generated['token']);

    expect($success)->toBeTrue();

    $user->refresh();
    expect($user->telegram_user_id)->toBe(987654321)
        ->and($user->telegram_username)->toBe('test_student');

    $tokenRecord = TelegramLinkToken::query()->where('user_id', $user->id)->first();
    expect($tokenRecord->used_at)->not->toBeNull();

    // Verify confirmation message was sent
    Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage') && $request['chat_id'] === 987654321);
});

it('rejects expired or invalid telegram link tokens', function (): void {
    $user = User::factory()->create();
    TelegramLinkToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', 'expired_token'),
        'expires_at' => now()->subMinutes(10),
    ]);

    $linkAction = app(LinkTelegramUser::class);
    $success = $linkAction->handle(987654321, 'test_student', 'expired_token');

    expect($success)->toBeFalse();
    $user->refresh();
    expect($user->telegram_user_id)->toBeNull();
});

it('allows an authenticated user to check status and unlink telegram', function (): void {
    $user = User::factory()->create([
        'telegram_user_id' => 12345678,
        'telegram_username' => 'linked_user',
    ]);

    $statusResponse = $this->actingAs($user)->getJson('/api/v1/telegram/status');
    $statusResponse->assertOk()
        ->assertJsonPath('data.is_linked', true)
        ->assertJsonPath('data.telegram_username', 'linked_user');

    $unlinkResponse = $this->actingAs($user)->postJson('/api/v1/telegram/unlink');
    $unlinkResponse->assertOk();

    $user->refresh();
    expect($user->telegram_user_id)->toBeNull()
        ->and($user->telegram_username)->toBeNull();
});
