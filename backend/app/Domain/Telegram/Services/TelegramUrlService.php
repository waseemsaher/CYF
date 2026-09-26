<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Services;

use App\Models\CourseItem;

class TelegramUrlService
{
    /**
     * Generate the Telegram private-channel message URL for a lesson.
     *
     * Private channel URLs follow the format:
     *   https://t.me/c/{channel_id_without_-100_prefix}/{message_id}
     *
     * The -100 prefix is Telegram's internal marker for supergroups/channels
     * and must be stripped to form the URL.
     */
    public function forLesson(CourseItem $item): ?string
    {
        $course = $item->course;

        if (! $course) {
            return null;
        }

        $channelId = $course->telegram_channel_id;
        $messageId = $item->telegram_message_id;

        if ($channelId === null || $messageId === null) {
            return null;
        }

        return $this->buildUrl((int) $channelId, (int) $messageId);
    }

    /**
     * Build a Telegram private-channel message URL from raw IDs.
     */
    public function buildUrl(int $channelId, int $messageId): string
    {
        // Strip the -100 prefix that Telegram uses internally for channels/supergroups
        $strippedId = $this->stripChannelPrefix($channelId);

        return "https://t.me/c/{$strippedId}/{$messageId}";
    }

    /**
     * Strip the -100 prefix from a Telegram channel/supergroup ID.
     *
     * Telegram channel IDs look like: -1001234567890
     * The URL-friendly form is: 1234567890 (without -100)
     */
    private function stripChannelPrefix(int $channelId): string
    {
        $idStr = (string) $channelId;

        // Remove leading -100 prefix
        if (str_starts_with($idStr, '-100')) {
            return substr($idStr, 4);
        }

        // If it's just negative (shouldn't happen for channels, but handle safely)
        if (str_starts_with($idStr, '-')) {
            return substr($idStr, 1);
        }

        return $idStr;
    }
}
