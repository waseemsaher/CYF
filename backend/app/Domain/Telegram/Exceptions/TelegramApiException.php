<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Exceptions;

use RuntimeException;
use Throwable;

class TelegramApiException extends RuntimeException
{
    public function __construct(
        public readonly string $method,
        public readonly int $errorCode,
        public readonly string $description,
        public readonly ?int $retryAfter = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            "Telegram API error in [{$method}] ({$errorCode}): {$description}",
            $errorCode,
            $previous
        );
    }

    public function isPermanent(): bool
    {
        // 403: bot blocked by user, user deactivated, bot kicked
        if ($this->errorCode === 403) {
            return true;
        }

        // 400: chat not found, user not found (except entity parse errors which can fall back to plain text)
        if ($this->errorCode === 400) {
            if ($this->isEntityParseError()) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function isTransient(): bool
    {
        return ! $this->isPermanent();
    }

    public function isEntityParseError(): bool
    {
        return $this->errorCode === 400 && str_contains(strtolower($this->description), "can't parse entities");
    }
}
