<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('registers a new student and returns a token', function (): void {
    Role::create(['name' => 'student']);

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Test Student',
        'email' => 'student@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'branch' => 'azhar_boys',
        'academic_year' => '1st',
        'department' => 'CS',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.user.email', 'student@example.com')
        ->assertJsonPath('data.user.role', 'student');

    $this->assertDatabaseHas('users', [
        'email' => 'student@example.com',
    ]);
});

it('logs in an existing user with valid credentials', function (): void {
    Role::create(['name' => 'student']);

    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => bcrypt('Password123!'),
    ]);

    $user->assignRole('student');

    $response = $this->postJson('/api/v1/login', [
        'email' => 'login@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.user.email', 'login@example.com')
        ->assertJsonPath('data.token', fn ($value) => is_string($value) && $value !== '');
});
