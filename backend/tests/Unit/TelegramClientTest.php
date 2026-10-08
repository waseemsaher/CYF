<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Telegram\Exceptions\TelegramApiException;
use App\Domain\Telegram\Services\TelegramClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramClientTest extends TestCase
{
    protected function tearDown(): void
    {
        TelegramClient::$sleepHandler = null;
        parent::tearDown();
    }

    public function test_telegram_api_exception_classifies_errors(): void
    {
        $perm403 = new TelegramApiException('sendMessage', 403, 'Forbidden: bot was blocked by the user');
        $this->assertTrue($perm403->isPermanent());
        $this->assertFalse($perm403->isTransient());
        $this->assertFalse($perm403->isEntityParseError());

        $perm400 = new TelegramApiException('sendMessage', 400, 'Bad Request: chat not found');
        $this->assertTrue($perm400->isPermanent());
        $this->assertFalse($perm400->isTransient());
        $this->assertFalse($perm400->isEntityParseError());

        $entity400 = new TelegramApiException('sendMessage', 400, "Bad Request: can't parse entities in message text");
        $this->assertFalse($entity400->isPermanent());
        $this->assertTrue($entity400->isTransient());
        $this->assertTrue($entity400->isEntityParseError());

        $transient429 = new TelegramApiException('sendMessage', 429, 'Too Many Requests', 5);
        $this->assertFalse($transient429->isPermanent());
        $this->assertTrue($transient429->isTransient());
        $this->assertSame(5, $transient429->retryAfter);
    }

    public function test_telegram_client_throws_telegram_api_exception_on_403_without_retrying(): void
    {
        $secretToken = '123456789:ABCdefGHIjklMNOpqrsTUVwxyz';
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => false,
                'error_code' => 403,
                'description' => 'Forbidden: bot was blocked by the user',
            ], 403),
        ]);

        $client = new TelegramClient($secretToken, 'https://api.telegram.org');

        $this->expectException(TelegramApiException::class);
        $this->expectExceptionCode(403);

        $client->sendMessage(123456, 'Hello');
    }

    public function test_telegram_client_retries_429_honoring_retry_after(): void
    {
        $secretToken = '123456789:ABCdefGHIjklMNOpqrsTUVwxyz';
        $sleepCount = 0;
        $sleepDuration = 0;
        TelegramClient::$sleepHandler = function (int $seconds) use (&$sleepCount, &$sleepDuration): void {
            $sleepCount++;
            $sleepDuration += $seconds;
        };

        Http::fake([
            'https://api.telegram.org/bot*' => Http::sequence()
                ->push(['ok' => false, 'error_code' => 429, 'description' => 'Too Many Requests', 'parameters' => ['retry_after' => 3]], 429)
                ->push(['ok' => true, 'result' => ['message_id' => 42]], 200),
        ]);

        $client = new TelegramClient($secretToken, 'https://api.telegram.org');
        $response = $client->sendMessage(123456, 'Hello');

        $this->assertTrue($response->successful());
        $this->assertSame(1, $sleepCount);
        $this->assertSame(3, $sleepDuration);
    }

    public function test_telegram_client_never_leaks_bot_token_in_exceptions_or_messages(): void
    {
        $secretToken = '987654321:SecretTokenToNeverLeakInLogs';
        $client = new TelegramClient($secretToken, 'https://api.telegram.org');

        // Test 1: sanitize helper cleans raw bot tokens and bot URLs
        $sampleUrl = "Failed to connect to https://api.telegram.org/bot{$secretToken}/sendMessage";
        $sanitized = $client->sanitize($sampleUrl);
        $this->assertStringNotContainsString($secretToken, $sanitized);
        $this->assertStringContainsString('[REDACTED_BOT_TOKEN]', $sanitized);

        // Test 2: Network connection exception scrubbing
        Http::fake([
            'https://api.telegram.org/bot*' => function () use ($secretToken): void {
                throw new ConnectionException("cURL error 28: Connection timed out to https://api.telegram.org/bot{$secretToken}/sendMessage");
            },
        ]);

        try {
            $client->sendMessage(123456, 'Hello');
            $this->fail('Expected TelegramApiException was not thrown');
        } catch (TelegramApiException $e) {
            $this->assertStringNotContainsString($secretToken, $e->getMessage());
            $this->assertStringContainsString('[REDACTED_BOT_TOKEN]', $e->getMessage());
        }
    }

    public function test_send_message_resends_without_parse_mode_on_entity_parse_error(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::sequence()
                ->push(['ok' => false, 'error_code' => 400, 'description' => "Bad Request: can't parse entities: Character '=' is reserved"], 400)
                ->push(['ok' => true, 'result' => ['message_id' => 99]], 200),
        ]);

        $client = new TelegramClient('test_token', 'https://api.telegram.org');
        $response = $client->sendMessage(123456, 'Broken <b>HTML');

        $this->assertTrue($response->successful());

        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            // Second request should have no parse_mode
            return ! isset($request->data()['parse_mode']);
        });
    }

    public function test_kick_chat_member_retries_unban_when_first_attempt_fails(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/banChatMember' => Http::response(['ok' => true], 200),
            'https://api.telegram.org/bot*/unbanChatMember' => Http::sequence()
                ->push(['ok' => false, 'error_code' => 500, 'description' => 'Internal server error'], 500)
                ->push(['ok' => true], 200),
        ]);

        $client = new TelegramClient('test_token', 'https://api.telegram.org');
        $result = $client->kickChatMember(-100123456, 789);

        $this->assertTrue($result);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'banChatMember'));
        Http::assertSent(fn ($req) => str_contains($req->url(), 'unbanChatMember'));
    }
}
