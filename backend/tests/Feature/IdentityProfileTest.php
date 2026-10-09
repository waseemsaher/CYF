<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('returns the authenticated user profile', function (): void {
    Role::create(['name' => 'student']);

    $user = User::factory()->create([
        'name' => 'Profile Student',
        'email' => 'profile@example.com',
        'branch' => 'azhar_boys',
        'academic_year' => '1st',
        'department' => 'CS',
        'locale' => 'ar',
    ]);
    $user->assignRole('student');

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/me');

    $response->assertOk()
        ->assertJsonPath('data.user.email', 'profile@example.com')
        ->assertJsonPath('data.user.name', 'Profile Student')
        ->assertJsonPath('data.user.telegram_is_linked', false);
});

it('returns telegram_is_linked true when telegram_user_id is present', function (): void {
    Role::create(['name' => 'student']);

    $user = User::factory()->create([
        'name' => 'Linked Student',
        'email' => 'linked@example.com',
        'telegram_user_id' => 123456789,
    ]);
    $user->assignRole('student');

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/me');

    $response->assertOk()
        ->assertJsonPath('data.user.telegram_is_linked', true);
});

it('updates the authenticated user profile', function (): void {
    Role::create(['name' => 'student']);

    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'update-profile@example.com',
        'locale' => 'ar',
    ]);
    $user->assignRole('student');

    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/v1/profile', [
        'name' => 'Updated Name',
        'telegram_username' => '@updated',
        'phone' => '01012345678',
        'locale' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.name', 'Updated Name')
        ->assertJsonPath('data.user.locale', 'en');

    $this->assertDatabaseHas('users', [
        'id' => $user->getKey(),
        'name' => 'Updated Name',
        'telegram_username' => '@updated',
        'phone' => '01012345678',
        'locale' => 'en',
    ]);
});

it('validates phone format when updating user profile', function (): void {
    Role::create(['name' => 'student']);

    $user = User::factory()->create([
        'email' => 'validate-profile@example.com',
    ]);
    $user->assignRole('student');

    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/v1/profile', [
        'phone' => '12345',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['phone'])
        ->assertJsonPath('errors.phone.0', 'رقم المحفظة يجب أن يكون رقم موبايل مصري صحيح مكوّن من 11 رقمًا (مثال: 01012345678).');
});
