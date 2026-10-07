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

test('password update revokes other personal access tokens but preserves current token', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    $currentToken = $user->createToken('current-device');
    $otherToken1 = $user->createToken('other-device-1');
    $otherToken2 = $user->createToken('other-device-2');

    expect($user->tokens()->count())->toBe(3);

    $response = $this->withHeader('Authorization', 'Bearer ' . $currentToken->plainTextToken)
        ->putJson('/api/v1/profile/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewSecretPassword456!',
            'password_confirmation' => 'NewSecretPassword456!',
        ]);

    $response->assertOk();

    $user->refresh();
    expect($user->tokens()->count())->toBe(1)
        ->and($user->tokens()->first()->id)->toBe($currentToken->accessToken->id);
});
