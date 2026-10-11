<?php

declare(strict_types=1);

use App\Models\CtaBlock;
use App\Models\User;
use Database\Seeders\CtaBlockSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::findOrCreate('superadmin', 'web');
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('teacher', 'web');
    Role::findOrCreate('student', 'web');

    $this->seed(CtaBlockSeeder::class);
});

it('seeds default CTA slots and serves them via public endpoint', function (): void {
    $response = $this->getJson('/api/v1/ctas');

    $response->assertOk()
        ->assertJsonPath('data.home_hero_primary.label_ar', 'ابدأ بمادة واحدة')
        ->assertJsonPath('data.home_hero_primary.is_visible', true)
        ->assertJsonPath('data.home_hero_secondary.label_ar', 'شوف الدرس التجريبي')
        ->assertJsonPath('data.announcement_bar.is_active', false)
        ->assertJsonPath('data.announcement_bar.is_visible', false);
});

it('enforces admin role on CTA management endpoints', function (): void {
    $student = User::factory()->create();
    $student->assignRole('student');

    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $this->actingAs($student)->getJson('/api/v1/admin/ctas')->assertForbidden();
    $this->actingAs($teacher)->getJson('/api/v1/admin/ctas')->assertForbidden();
    $this->actingAs($student)->postJson('/api/v1/admin/ctas/home_hero_primary/toggle')->assertForbidden();
});

it('allows admin to update CTA slot, sanitizes HTML, validates URL and date window', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Invalid URL (javascript: or http://) must fail validation
    $this->actingAs($admin)->putJson('/api/v1/admin/ctas/home_hero_primary', [
        'label_ar' => 'اشترك الآن',
        'url' => 'javascript:alert(1)',
        'style' => 'primary',
        'is_active' => true,
        'open_in_new_tab' => false,
    ])->assertStatus(422);

    $this->actingAs($admin)->putJson('/api/v1/admin/ctas/home_hero_primary', [
        'label_ar' => 'اشترك الآن',
        'url' => 'http://insecure.example.com',
        'style' => 'primary',
        'is_active' => true,
        'open_in_new_tab' => false,
    ])->assertStatus(422);

    // Invalid date window (ends_at before starts_at) must fail validation
    $this->actingAs($admin)->putJson('/api/v1/admin/ctas/home_hero_primary', [
        'label_ar' => 'اشترك الآن',
        'url' => '/courses',
        'style' => 'primary',
        'is_active' => true,
        'starts_at' => '2026-11-10T10:00:00Z',
        'ends_at' => '2026-11-01T10:00:00Z',
        'open_in_new_tab' => false,
    ])->assertStatus(422);

    // Valid update with HTML tags stripped
    $updateRes = $this->actingAs($admin)->putJson('/api/v1/admin/ctas/home_hero_primary', [
        'label_ar' => '<script>alert(1)</script>ابدأ بمادة واحدة الآن',
        'headline_ar' => '<b>عنوان تجريبي</b>',
        'subtext_ar' => 'وصف قصير بدون HTML',
        'url' => 'https://wa.me/201555444726',
        'style' => 'primary',
        'is_active' => true,
        'open_in_new_tab' => true,
    ]);

    $updateRes->assertOk()
        ->assertJsonPath('data.label_ar', 'alert(1)ابدأ بمادة واحدة الآن')
        ->assertJsonPath('data.headline_ar', 'عنوان تجريبي')
        ->assertJsonPath('data.open_in_new_tab', true)
        ->assertJsonPath('data.updated_by', $admin->id);
});

it('toggles CTA active status in one click and respects starts_at / ends_at schedule', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $toggleRes = $this->actingAs($admin)->postJson('/api/v1/admin/ctas/home_hero_primary/toggle');
    $toggleRes->assertOk()
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.status', 'inactive')
        ->assertJsonPath('data.is_visible', false);

    // Expired window should report status = expired and is_visible = false
    CtaBlock::query()->where('key', 'announcement_bar')->update([
        'is_active' => true,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->subDay(),
    ]);

    $adminList = $this->actingAs($admin)->getJson('/api/v1/admin/ctas');
    $adminList->assertOk();

    $announcement = collect($adminList->json('data'))->firstWhere('key', 'announcement_bar');
    expect($announcement['status'])->toBe('expired')
        ->and($announcement['is_visible'])->toBeFalse();
});

