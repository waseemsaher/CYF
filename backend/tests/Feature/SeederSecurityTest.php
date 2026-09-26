<?php

declare(strict_types=1);

use Database\Seeders\DemoAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;

uses(RefreshDatabase::class);

test('demo accounts seeder cannot run in production environment', function (): void {
    App::detectEnvironment(fn () => 'production');

    expect(fn () => (new DemoAccountsSeeder)->run())
        ->toThrow(RuntimeException::class, 'SECURITY ERROR: Demo accounts seeder cannot be run in non-local environments.');
});

test('database seeder does not seed demo users in production', function (): void {
    App::detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $this->assertDatabaseMissing('users', [
        'email' => 'admin@example.com',
    ]);
    $this->assertDatabaseMissing('users', [
        'email' => 'student@example.com',
    ]);
    $this->assertDatabaseMissing('users', [
        'email' => 'teacher@example.com',
    ]);
});
