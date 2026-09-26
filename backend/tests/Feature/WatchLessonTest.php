<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\CourseItem;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->course = Course::create([
        'slug' => 'distributed-systems',
        'title' => ['ar' => 'نظم موزعة', 'en' => 'Distributed Systems'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 20000,
        'telegram_channel_id' => -1001234567890,
        'telegram_group_id' => -1009876543210,
    ]);

    $this->section = CourseSection::create([
        'course_id' => $this->course->id,
        'title' => ['ar' => 'الفصل الأول', 'en' => 'Chapter 1'],
        'position' => 1,
    ]);

    $this->item = CourseItem::create([
        'course_id' => $this->course->id,
        'section_id' => $this->section->id,
        'type' => 'lecture_link',
        'title' => ['ar' => 'الدرس الأول', 'en' => 'Lesson 1'],
        'position' => 1,
        'is_published' => true,
        'telegram_message_id' => 142,
    ]);

    $this->term = Term::create([
        'name' => ['ar' => 'فصل 1', 'en' => 'Term 1'],
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonths(2),
        'is_current' => true,
    ]);
});

it('rejects unauthenticated requests to watch lesson', function (): void {
    $response = $this->getJson("/api/v1/lessons/{$this->item->id}/watch");

    $response->assertUnauthorized();
});

it('returns 403 when user is not enrolled in the course', function (): void {
    $user = User::factory()->create([
        'telegram_user_id' => 112233,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/lessons/{$this->item->id}/watch");

    $response->assertForbidden();
});

it('returns 403 when user enrollment has expired', function (): void {
    $user = User::factory()->create([
        'telegram_user_id' => 112233,
    ]);

    Enrollment::create([
        'user_id' => $user->id,
        'course_id' => $this->course->id,
        'term_id' => $this->term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subMonths(2),
        'expires_at' => now()->subDay(),
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/lessons/{$this->item->id}/watch");

    $response->assertForbidden();
});

it('returns 422 when enrolled user has not linked telegram account', function (): void {
    $user = User::factory()->create([
        'telegram_user_id' => null,
    ]);

    Enrollment::create([
        'user_id' => $user->id,
        'course_id' => $this->course->id,
        'term_id' => $this->term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/lessons/{$this->item->id}/watch");

    $response->assertStatus(422);
});

it('returns 404 when lesson does not have a telegram message mapped', function (): void {
    $user = User::factory()->create([
        'telegram_user_id' => 112233,
    ]);

    Enrollment::create([
        'user_id' => $user->id,
        'course_id' => $this->course->id,
        'term_id' => $this->term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
    ]);

    $itemWithoutVideo = CourseItem::create([
        'course_id' => $this->course->id,
        'section_id' => $this->section->id,
        'type' => 'file',
        'title' => ['ar' => 'مستند', 'en' => 'Doc'],
        'position' => 2,
        'is_published' => true,
        'telegram_message_id' => null,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/lessons/{$itemWithoutVideo->id}/watch");

    $response->assertNotFound();
});

it('returns telegram message URL for enrolled student with linked telegram account', function (): void {
    $user = User::factory()->create([
        'telegram_user_id' => 112233,
    ]);

    Enrollment::create([
        'user_id' => $user->id,
        'course_id' => $this->course->id,
        'term_id' => $this->term->id,
        'source' => 'payment',
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/lessons/{$this->item->id}/watch");

    $response->assertOk()
        ->assertJson([
            'data' => [
                'url' => 'https://t.me/c/1234567890/142',
            ],
        ]);
});

it('allows admin staff to watch lesson even without enrollment', function (): void {
    Role::create(['name' => 'admin']);

    $admin = User::factory()->create([
        'telegram_user_id' => 999999,
    ]);
    $admin->assignRole('admin');

    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/v1/lessons/{$this->item->id}/watch");

    $response->assertOk()
        ->assertJson([
            'data' => [
                'url' => 'https://t.me/c/1234567890/142',
            ],
        ]);
});
