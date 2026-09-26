<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('authenticated user can change password and must_change_password becomes false', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
        'must_change_password' => true,
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'NewSecretPassword456!',
        'password_confirmation' => 'NewSecretPassword456!',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.must_change_password', false);

    $user->refresh();
    expect($user->must_change_password)->toBeFalse()
        ->and(Hash::check('NewSecretPassword456!', $user->password))->toBeTrue();
});

test('password update fails with invalid current password', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
        'must_change_password' => true,
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/profile/password', [
        'current_password' => 'WrongPassword!',
        'password' => 'NewSecretPassword456!',
        'password_confirmation' => 'NewSecretPassword456!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['current_password']);
});
