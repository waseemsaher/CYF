<?php

declare(strict_types=1);

use App\Models\Course;

it('normalizes negative telegram chat IDs missing the 100 prefix', function (): void {
    $course = new Course;
    $course->telegram_channel_id = '-4331858990';
    $course->telegram_group_id = '-3639967800';

    expect($course->telegram_channel_id)->toBe('-1004331858990')
        ->and($course->telegram_group_id)->toBe('-1003639967800');
});

it('preserves telegram chat IDs already containing the -100 prefix', function (): void {
    $course = new Course;
    $course->telegram_channel_id = '-1004331858990';
    $course->telegram_group_id = '-1003639967800';

    expect($course->telegram_channel_id)->toBe('-1004331858990')
        ->and($course->telegram_group_id)->toBe('-1003639967800');
});

it('preserves invite links or urls without modification', function (): void {
    $course = new Course;
    $course->telegram_channel_id = 'https://t.me/+joinchat_test';

    expect($course->telegram_channel_id)->toBe('https://t.me/+joinchat_test');
});
