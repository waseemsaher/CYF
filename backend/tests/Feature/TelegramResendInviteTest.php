<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Telegram\Services\TelegramClient;
use App\Jobs\SendCourseInviteLinkJob;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\TelegramCourseInvite;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TelegramResendInviteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('student', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('superadmin', 'web');
    }

    public function test_unauthenticated_user_cannot_resend_invite(): void
    {
        $course = Course::create([
            'slug' => 'bio',
            'title' => ['ar' => 'أحياء', 'en' => 'Biology'],
            'description' => ['ar' => 'وصف', 'en' => 'Desc'],
            'price_cents' => 10000,
            'status' => 'published',
            'telegram_group_id' => -100123456,
        ]);

        $this->postJson("/api/v1/telegram/courses/{$course->id}/resend-invite")
            ->assertUnauthorized();
    }

    public function test_unlinked_student_cannot_resend_invite(): void
    {
        $student = User::factory()->create(['telegram_user_id' => null]);
        $student->assignRole('student');

        $course = Course::create([
            'slug' => 'bio',
            'title' => ['ar' => 'أحياء', 'en' => 'Biology'],
            'description' => ['ar' => 'وصف', 'en' => 'Desc'],
            'price_cents' => 10000,
            'status' => 'published',
            'telegram_group_id' => -100123456,
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/telegram/courses/{$course->id}/resend-invite")
            ->assertStatus(422)
            ->assertJsonPath('message', 'يجب ربط حسابك في تليجرام أولاً لاستلام رابط الانضمام.');
    }

    public function test_student_without_active_enrollment_cannot_resend_invite(): void
    {
        $student = User::factory()->create(['telegram_user_id' => 123456]);
        $student->assignRole('student');

        $course = Course::create([
            'slug' => 'bio',
            'title' => ['ar' => 'أحياء', 'en' => 'Biology'],
            'description' => ['ar' => 'وصف', 'en' => 'Desc'],
            'price_cents' => 10000,
            'status' => 'published',
            'telegram_group_id' => -100123456,
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/telegram/courses/{$course->id}/resend-invite")
            ->assertStatus(403);
    }

    public function test_enrolled_student_with_linked_telegram_revokes_outstanding_and_dispatches_job(): void
    {
        Queue::fake();

        $student = User::factory()->create(['telegram_user_id' => 123456]);
        $student->assignRole('student');

        $course = Course::create([
            'slug' => 'bio',
            'title' => ['ar' => 'أحياء', 'en' => 'Biology'],
            'description' => ['ar' => 'وصف', 'en' => 'Desc'],
            'price_cents' => 10000,
            'status' => 'published',
            'telegram_group_id' => -100123456,
        ]);

        $term = Term::create([
            'name' => ['ar' => 'فصل', 'en' => 'Term'],
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonths(2),
            'is_current' => true,
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'term_id' => $term->id,
            'source' => 'admin_grant',
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addMonths(2),
        ]);

        $outstanding = TelegramCourseInvite::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'invite_link' => 'https://t.me/+old_link',
            'expires_at' => now()->addHours(24),
        ]);

        /** @var TelegramClient&Mockery\MockInterface $clientMock */
        $clientMock = Mockery::mock(TelegramClient::class);
        $clientMock->shouldReceive('revokeChatInviteLink')
            ->once()
            ->with(-100123456, 'https://t.me/+old_link');
        $this->app->instance(TelegramClient::class, $clientMock);

        $response = $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/telegram/courses/{$course->id}/resend-invite");

        $response->assertOk()
            ->assertJsonPath('message', 'تم إرسال رابط الانضمام الجديد إلى حسابك على تليجرام بنجاح.');

        $this->assertDatabaseMissing('telegram_course_invites', [
            'id' => $outstanding->id,
        ]);

        Queue::assertPushed(SendCourseInviteLinkJob::class, function (SendCourseInviteLinkJob $job) use ($student, $course): bool {
            return $job->user->getKey() === $student->getKey()
                && $job->course->getKey() === $course->getKey();
        });
    }

    public function test_staff_allowed_to_resend_invite_without_enrollment(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['telegram_user_id' => 999999]);
        $admin->assignRole('admin');

        $course = Course::create([
            'slug' => 'bio',
            'title' => ['ar' => 'أحياء', 'en' => 'Biology'],
            'description' => ['ar' => 'وصف', 'en' => 'Desc'],
            'price_cents' => 10000,
            'status' => 'published',
            'telegram_group_id' => -100123456,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/telegram/courses/{$course->id}/resend-invite");

        $response->assertOk();

        Queue::assertPushed(SendCourseInviteLinkJob::class);
    }
}
