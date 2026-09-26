<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('telegram.bot_token', 'test_token');

    $role = Role::create(['name' => 'admin']);
    Permission::create(['name' => 'courses.manage']);
    $role->givePermissionTo('courses.manage');

    $this->course = Course::create([
        'slug' => 'networks',
        'title' => ['ar' => 'شبكات', 'en' => 'Networks'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'published',
        'price_cents' => 15000,
        'telegram_channel_id' => -1001112223334,
        'telegram_group_id' => -1005556667778,
    ]);
});

it('rejects unauthenticated requests to test telegram connection', function (): void {
    $response = $this->postJson("/api/v1/admin/courses/{$this->course->id}/telegram/test");

    $response->assertUnauthorized();
});

it('rejects non-admin requests to test telegram connection', function (): void {
    $student = User::factory()->create();
    Sanctum::actingAs($student);

    $response = $this->postJson("/api/v1/admin/courses/{$this->course->id}/telegram/test");

    $response->assertForbidden();
});

it('tests connection to channel and group successfully for admin', function (): void {
    Http::fake([
        'https://api.telegram.org/bottest_token/getChat' => function (Request $request) {
            $chatId = $request['chat_id'];
            if ($chatId == -1001112223334) {
                return Http::response([
                    'ok' => true,
                    'result' => [
                        'id' => -1001112223334,
                        'title' => 'Networks Official Channel',
                        'type' => 'channel',
                    ],
                ], 200);
            }
            if ($chatId == -1005556667778) {
                return Http::response([
                    'ok' => true,
                    'result' => [
                        'id' => -1005556667778,
                        'title' => 'Networks Discussion Group',
                        'type' => 'supergroup',
                    ],
                ], 200);
            }

            return Http::response(['ok' => false, 'description' => 'Chat not found'], 400);
        },
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $response = $this->postJson("/api/v1/admin/courses/{$this->course->id}/telegram/test");

    $response->assertOk()
        ->assertJson([
            'data' => [
                'channel' => [
                    'connected' => true,
                    'title' => 'Networks Official Channel',
                    'error' => null,
                ],
                'group' => [
                    'connected' => true,
                    'title' => 'Networks Discussion Group',
                    'error' => null,
                ],
            ],
        ]);
});

it('reports failures when bot is not an admin or chat is not found', function (): void {
    Http::fake([
        'https://api.telegram.org/bottest_token/getChat' => Http::response([
            'ok' => false,
            'error_code' => 400,
            'description' => 'Bad Request: chat not found',
        ], 400),
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $response = $this->postJson("/api/v1/admin/courses/{$this->course->id}/telegram/test");

    $response->assertOk()
        ->assertJson([
            'data' => [
                'channel' => [
                    'connected' => false,
                    'title' => null,
                    'error' => 'Bad Request: chat not found',
                ],
                'group' => [
                    'connected' => false,
                    'title' => null,
                    'error' => 'Bad Request: chat not found',
                ],
            ],
        ]);
});

it('reports unconfigured status when channel or group id is missing', function (): void {
    $courseWithoutIds = Course::create([
        'slug' => 'db101',
        'title' => ['ar' => 'قواعد بيانات', 'en' => 'Databases'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'status' => 'draft',
        'price_cents' => 10000,
        'telegram_channel_id' => null,
        'telegram_group_id' => null,
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $response = $this->postJson("/api/v1/admin/courses/{$courseWithoutIds->id}/telegram/test");

    $response->assertOk()
        ->assertJson([
            'data' => [
                'channel' => [
                    'connected' => false,
                    'title' => null,
                    'error' => 'No Telegram channel ID configured.',
                ],
                'group' => [
                    'connected' => false,
                    'title' => null,
                    'error' => 'No Telegram group ID configured.',
                ],
            ],
        ]);
});
