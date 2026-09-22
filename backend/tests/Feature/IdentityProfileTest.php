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
        ->assertJsonPath('data.user.name', 'Profile Student');
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
        'phone' => '+966500000000',
        'locale' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.name', 'Updated Name')
        ->assertJsonPath('data.user.locale', 'en');

    $this->assertDatabaseHas('users', [
        'id' => $user->getKey(),
        'name' => 'Updated Name',
        'telegram_username' => '@updated',
        'locale' => 'en',
    ]);
});
