<?php

declare(strict_types=1);

use App\Domain\Catalog\Actions\ManageCourseAction;
use App\Domain\Catalog\Actions\ManageDiscountAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function adminUserForActivityLog(): User
{
    $permission = Permission::findOrCreate('courses.manage', 'web');
    $role = Role::findOrCreate('admin', 'web');
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('allows a permitted admin to list paginated activity logs', function (): void {
    $admin = adminUserForActivityLog();

    for ($i = 1; $i <= 30; $i++) {
        activity('system')
            ->causedBy($admin)
            ->log("Action item {$i}");
    }

    $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/activity-logs?per_page=10&page=1');

    $response->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 30)
        ->assertJsonPath('meta.last_page', 3)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'description',
                    'log_name',
                    'causer_name',
                    'created_at',
                    'subject_type',
                    'subject_id',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
});

it('filters activity logs by log_name, causer_id, and date range', function (): void {
    $admin = adminUserForActivityLog();
    $otherUser = User::factory()->create();

    $paymentAct = activity('payment')
        ->causedBy($admin)
        ->createdAt(now()->subDays(5))
        ->log('Payment processed');

    $authAct = activity('auth')
        ->causedBy($otherUser)
        ->createdAt(now()->subDay())
        ->log('User login');

    // Filter by log_name
    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/activity-logs?log_name=payment')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.log_name', 'payment')
        ->assertJsonPath('data.0.id', $paymentAct->id);

    // Filter by causer_id
    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/activity-logs?causer_id='.$otherUser->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.causer_name', $otherUser->name)
        ->assertJsonPath('data.0.id', $authAct->id);

    // Filter by date_from and date_to
    $dateFrom = now()->subDays(6)->toDateString();
    $dateTo = now()->subDays(4)->toDateString();

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/admin/activity-logs?date_from={$dateFrom}&date_to={$dateTo}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $paymentAct->id);
});

it('denies activity log access to students and unauthenticated users', function (): void {
    $student = User::factory()->create();
    $student->assignRole(Role::findOrCreate('student', 'web'));

    $this->actingAs($student, 'sanctum')
        ->getJson('/api/v1/admin/activity-logs')
        ->assertForbidden();

    $this->getJson('/api/v1/admin/activity-logs')
        ->assertForbidden();
});

it('logs course and discount management actions and auth logins', function (): void {
    $admin = adminUserForActivityLog();

    // 1. ManageCourseAction creates activity log
    $courseAction = app(ManageCourseAction::class);
    $this->actingAs($admin, 'sanctum');
    $course = $courseAction->create([
        'slug' => 'logged-course',
        'title' => ['ar' => 'دورة مسجلة', 'en' => 'Logged Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
        'status' => 'draft',
    ]);

    $courseLog = Activity::query()
        ->where('log_name', 'courses')
        ->where('subject_id', $course->id)
        ->first();

    expect($courseLog)->not->toBeNull();
    expect($courseLog?->description)->toContain('Created course');
    expect($courseLog?->causer_id)->toBe($admin->id);

    // 2. ManageDiscountAction creates activity log
    $discountAction = app(ManageDiscountAction::class);
    $discount = $discountAction->create([
        'name' => ['ar' => 'خصم نشط', 'en' => 'Active Discount'],
        'type' => 'percent',
        'value' => 20,
        'scope' => 'all',
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDays(7)->toDateString(),
        'is_active' => true,
    ]);

    $discountLog = Activity::query()
        ->where('log_name', 'discounts')
        ->where('subject_id', $discount->id)
        ->first();

    expect($discountLog)->not->toBeNull();
    expect($discountLog?->description)->toContain('Created discount');
    expect($discountLog?->causer_id)->toBe($admin->id);

    // 3. Auth login creates activity log
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password123',
    ])->assertOk();

    $authLog = Activity::query()
        ->where('log_name', 'auth')
        ->where('causer_id', $user->id)
        ->first();

    expect($authLog)->not->toBeNull();
    expect($authLog?->description)->toBe('Logged in');
});
