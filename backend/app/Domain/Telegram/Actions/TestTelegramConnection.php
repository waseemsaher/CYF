<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Actions;

use App\Domain\Telegram\Services\TelegramClient;
use App\Models\Course;
use Illuminate\Support\Facades\Log;

class TestTelegramConnection
{
    public function __construct(
        private readonly TelegramClient $client,
    ) {}

    /**
     * Test the bot's ability to access the configured Telegram channel and group for a course.
     *
     * @return array{channel: array{connected: bool, title: string|null, error: string|null}, group: array{connected: bool, title: string|null, error: string|null}}
     */
    public function handle(Course $course): array
    {
        return [
            'channel' => $this->testChat($course->telegram_channel_id, 'channel', $course),
            'group' => $this->testChat($course->telegram_group_id, 'group', $course),
        ];
    }

    /**
     * @return array{connected: bool, title: string|null, error: string|null}
     */
    private function testChat(int|string|null $chatId, string $type, Course $course): array
    {
        if ($chatId === null) {
            return [
                'connected' => false,
                'title' => null,
                'error' => "No Telegram {$type} ID configured.",
            ];
        }

        try {
            $response = $this->client->getChat($chatId);

            if ($response->successful()) {
                $data = $response->json('result', []);

                Log::info("Telegram {$type} connection test successful", [
                    'course_id' => $course->id,
                    'chat_id' => $chatId,
                    'chat_title' => $data['title'] ?? null,
                ]);

                return [
                    'connected' => true,
                    'title' => $data['title'] ?? null,
                    'error' => null,
                ];
            }

            $errorDescription = $response->json('description', 'Unknown error');

            Log::warning("Telegram {$type} connection test failed", [
                'course_id' => $course->id,
                'chat_id' => $chatId,
                'error' => $errorDescription,
            ]);

            return [
                'connected' => false,
                'title' => null,
                'error' => $errorDescription,
            ];
        } catch (\Throwable $e) {
            Log::error("Telegram {$type} connection test error", [
                'course_id' => $course->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return [
                'connected' => false,
                'title' => null,
                'error' => 'Connection failed: '.$e->getMessage(),
            ];
        }
    }
}
