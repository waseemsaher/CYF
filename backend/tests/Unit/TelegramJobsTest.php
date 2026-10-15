<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Telegram\Actions\SendCourseInviteLink;
use App\Domain\Telegram\Exceptions\TelegramApiException;
use App\Domain\Telegram\Services\TelegramClient;
use App\Jobs\SendCourseInviteLinkJob;
use App\Jobs\SendTelegramNotificationJob;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class TelegramJobsTest extends TestCase
{
    public function test_send_telegram_notification_job_configuration_and_handling(): void
    {
        $job = new SendTelegramNotificationJob(123456, 'Hello');
        $job->withFakeQueueInteractions();

        $this->assertSame(5, $job->tries);
        $this->assertSame([10, 60, 300, 900], $job->backoff());

        /** @var TelegramClient&Mockery\MockInterface $clientMock */
        $clientMock = Mockery::mock(TelegramClient::class);
        $clientMock->shouldReceive('sendMessage')
            ->once()
            ->andThrow(new TelegramApiException('sendMessage', 403, 'Forbidden: bot was blocked by user'));

        $job->handle($clientMock);
        $job->assertFailed();

        // Transient error -> rethrows so queue retries
        $job2 = new SendTelegramNotificationJob(123456, 'Hello');
        /** @var TelegramClient&Mockery\MockInterface $clientMock2 */
        $clientMock2 = Mockery::mock(TelegramClient::class);
        $clientMock2->shouldReceive('sendMessage')
            ->once()
            ->andThrow(new TelegramApiException('sendMessage', 502, 'Bad Gateway'));

        $this->expectException(TelegramApiException::class);
        $job2->handle($clientMock2);
    }

    public function test_send_course_invite_link_job_configuration_and_handling(): void
    {
        $user = new User(['id' => 1, 'name' => 'Alice']);
        $course = new Course(['id' => 2, 'slug' => 'math']);
        $job = new SendCourseInviteLinkJob($user, $course);
        $job->withFakeQueueInteractions();

        $this->assertSame(5, $job->tries);
        $this->assertSame([10, 60, 300, 900], $job->backoff());

        /** @var SendCourseInviteLink&Mockery\MockInterface $actionMock */
        $actionMock = Mockery::mock(SendCourseInviteLink::class);
        $actionMock->shouldReceive('handle')
            ->once()
            ->andThrow(new TelegramApiException('createChatInviteLink', 400, 'Bad Request: chat not found'));

        $job->handle($actionMock);
        $job->assertFailed();

        // Logging on failed()
        Log::shouldReceive('error')
            ->once()
            ->with('SendCourseInviteLinkJob permanently failed', Mockery::on(function (array $context): bool {
                return $context['error_code'] === 400 && str_contains((string) $context['description'], 'chat not found');
            }));

        $job->failed(new TelegramApiException('createChatInviteLink', 400, 'Bad Request: chat not found'));
    }
}
