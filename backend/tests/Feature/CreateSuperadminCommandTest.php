<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('it creates a superadmin user with random password and must_change_password true', function (): void {
    Role::create(['name' => 'superadmin', 'guard_name' => 'web']);

    $this->artisan('admin:create-superadmin', [
        'email' => 'admin.real@example.com',
        '--name' => 'Root Admin',
    ])
        ->expectsOutputToContain('Superadmin account created successfully.')
        ->expectsOutputToContain('Email: admin.real@example.com')
        ->expectsOutputToContain('Password: ')
        ->assertSuccessful();

    $user = User::where('email', 'admin.real@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Root Admin')
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->is_active)->toBeTrue()
        ->and($user->hasRole('superadmin'))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
});

test('it fails when email is invalid', function (): void {
    $this->artisan('admin:create-superadmin', [
        'email' => 'invalid-email',
    ])
        ->expectsOutput('The provided email address is invalid.')
        ->assertFailed();
});

test('it fails when email already exists', function (): void {
    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $this->artisan('admin:create-superadmin', [
        'email' => 'existing@example.com',
    ])
        ->expectsOutput('A user with email [existing@example.com] already exists.')
        ->assertFailed();
});
