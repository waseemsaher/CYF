<?php

declare(strict_types=1);

use App\Domain\Telegram\Services\TelegramUrlService;
use App\Models\Course;
use App\Models\CourseItem;

it('generates a private telegram message url by stripping the -100 prefix', function (): void {
    $service = new TelegramUrlService;

    $url = $service->buildUrl(-1001234567890, 42);

    expect($url)->toBe('https://t.me/c/1234567890/42');
});

it('handles negative channel IDs without -100 prefix safely', function (): void {
    $service = new TelegramUrlService;

    $url = $service->buildUrl(-1234567890, 42);

    expect($url)->toBe('https://t.me/c/1234567890/42');
});

it('handles positive channel IDs safely', function (): void {
    $service = new TelegramUrlService;

    $url = $service->buildUrl(1234567890, 42);

    expect($url)->toBe('https://t.me/c/1234567890/42');
});

it('returns null if course or telegram_channel_id or telegram_message_id is missing', function (): void {
    $service = new TelegramUrlService;

    $courseWithoutChannel = new Course;
    $courseWithoutChannel->telegram_channel_id = null;

    $itemWithoutMessage = new CourseItem;
    $itemWithoutMessage->setRelation('course', $courseWithoutChannel);
    $itemWithoutMessage->telegram_message_id = null;

    expect($service->forLesson($itemWithoutMessage))->toBeNull();

    $courseWithChannel = new Course;
    $courseWithChannel->telegram_channel_id = -100999888777;

    $itemWithoutMsgId = new CourseItem;
    $itemWithoutMsgId->setRelation('course', $courseWithChannel);
    $itemWithoutMsgId->telegram_message_id = null;

    expect($service->forLesson($itemWithoutMsgId))->toBeNull();

    $itemWithMsgId = new CourseItem;
    $itemWithMsgId->setRelation('course', $courseWithChannel);
    $itemWithMsgId->telegram_message_id = 99;

    expect($service->forLesson($itemWithMsgId))->toBe('https://t.me/c/999888777/99');
});
