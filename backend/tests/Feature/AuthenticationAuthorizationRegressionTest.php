<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
});

it('ensures new registrations create ordinary student users with least-privileged default role and unique id', function (): void {
    $res1 = $this->postJson('/api/v1/register', [
        'name' => 'User One',
        'email' => 'user1@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'branch' => 'azhar_boys',
        'academic_year' => '1st',
        'department' => 'CS',
        'phone' => '01011111111',
    ]);
    $res1->assertStatus(201);
    $user1Id = $res1->json('data.user.id');
    expect($res1->json('data.user.role'))->toBe('student');

    $res2 = $this->postJson('/api/v1/register', [
        'name' => 'User Two',
        'email' => 'user2@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'branch' => 'azhar_girls',
        'academic_year' => '2nd',
        'department' => 'IS',
        'phone' => '01022222222',
    ]);
    $res2->assertStatus(201);
    $user2Id = $res2->json('data.user.id');
    expect($res2->json('data.user.role'))->toBe('student');

    expect($user1Id)->not->toBe($user2Id);
});

it('verifies two different users and an admin in separate sessions only see their own profile data', function (): void {
    $admin = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('AdminPass123!'),
    ]);
    $admin->assignRole('superadmin');

    $student1 = User::factory()->create([
        'email' => 'student1@example.com',
        'password' => bcrypt('StudentPass123!'),
    ]);
    $student1->assignRole('student');

    $student2 = User::factory()->create([
        'email' => 'student2@example.com',
        'password' => bcrypt('StudentPass123!'),
    ]);
    $student2->assignRole('student');

    $tokenAdmin = $admin->createToken('admin-token')->plainTextToken;
    $token1 = $student1->createToken('s1-token')->plainTextToken;
    $token2 = $student2->createToken('s2-token')->plainTextToken;

    // Admin profile check
    $resAdmin = $this->withHeader('Authorization', 'Bearer '.$tokenAdmin)->getJson('/api/v1/me');
    $resAdmin->assertOk()
        ->assertJsonPath('data.user.email', 'admin@example.com')
        ->assertJsonPath('data.user.role', 'superadmin');

    // Student 1 profile check
    $res1 = $this->withHeader('Authorization', 'Bearer '.$token1)->getJson('/api/v1/me');
    $res1->assertOk()
        ->assertJsonPath('data.user.email', 'student1@example.com')
        ->assertJsonPath('data.user.role', 'student');

    // Student 2 profile check
    $res2 = $this->withHeader('Authorization', 'Bearer '.$token2)->getJson('/api/v1/me');
    $res2->assertOk()
        ->assertJsonPath('data.user.email', 'student2@example.com')
        ->assertJsonPath('data.user.role', 'student');
});

it('ensures ordinary users cannot access admin endpoints while existing admin access continues to work', function (): void {
    $admin = User::factory()->create(['email' => 'admin-access@example.com']);
    $admin->assignRole('superadmin');
    $adminToken = $admin->createToken('admin-token')->plainTextToken;

    $student = User::factory()->create(['email' => 'student-access@example.com']);
    $student->assignRole('student');
    $studentToken = $student->createToken('student-token')->plainTextToken;

    // Admin access works
    $this->withHeader('Authorization', 'Bearer '.$adminToken)
        ->getJson('/api/v1/admin/overview')
        ->assertOk();

    // Student is strictly forbidden
    $this->withHeader('Authorization', 'Bearer '.$studentToken)
        ->getJson('/api/v1/admin/overview')
        ->assertForbidden();

    $this->withHeader('Authorization', 'Bearer '.$studentToken)
        ->getJson('/api/v1/admin/students')
        ->assertForbidden();

    $this->withHeader('Authorization', 'Bearer '.$studentToken)
        ->getJson('/api/v1/admin/payments')
        ->assertForbidden();
});

it('prevents new registration from resolving to admin profile when an admin session exists', function (): void {
    $admin = User::factory()->create([
        'email' => 'admin-session@example.com',
        'password' => bcrypt('password123'),
    ]);
    $admin->assignRole('superadmin');

    config([
        'sanctum.stateful' => ['localhost', '127.0.0.1'],
    ]);

    // Admin logs in and establishes stateful session
    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => 'admin-session@example.com',
        'password' => 'password123',
    ]);
    $loginResponse->assertOk();

    // Now a new user registers in the same browser session
    $registerResponse = $this->postJson('/api/v1/register', [
        'name' => 'Alice Registered',
        'email' => 'alice-registered@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'branch' => 'azhar_boys',
        'academic_year' => '1st',
        'department' => 'CS',
        'phone' => '01012345678',
    ]);
    $registerResponse->assertStatus(201);
    $aliceToken = $registerResponse->json('data.token');

    // Alice requests her profile on /me with her Bearer token
    $meResponse = $this->withHeader('Authorization', 'Bearer '.$aliceToken)
        ->getJson('/api/v1/me');

    $meResponse->assertOk();
    expect($meResponse->json('data.user.email'))->toBe('alice-registered@example.com');
    expect($meResponse->json('data.user.role'))->toBe('student');

    // Alice attempts to access admin endpoint
    $adminAttempt = $this->withHeader('Authorization', 'Bearer '.$aliceToken)
        ->getJson('/api/v1/admin/overview');
    $adminAttempt->assertForbidden();
});

it('clears session on logout and prevents stale user reuse upon subsequent registration', function (): void {
    $admin = User::factory()->create([
        'email' => 'admin-logout@example.com',
        'password' => bcrypt('password123'),
    ]);
    $admin->assignRole('superadmin');

    config([
        'sanctum.stateful' => ['localhost', '127.0.0.1'],
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => 'admin-logout@example.com',
        'password' => 'password123',
    ]);
    $loginResponse->assertOk();
    $adminToken = $loginResponse->json('data.token');

    // Admin logs out via /api/v1/logout
    $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
        ->postJson('/api/v1/logout');
    $logoutResponse->assertOk();

    // Subsequent request with the revoked token must fail with 401
    $revokedCheck = $this->withHeader('Authorization', 'Bearer '.$adminToken)
        ->getJson('/api/v1/me');
    $revokedCheck->assertUnauthorized();

    // Now a new user registers in the same session
    $registerResponse = $this->postJson('/api/v1/register', [
        'name' => 'Bob Registered',
        'email' => 'bob-registered@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'branch' => 'azhar_boys',
        'academic_year' => '1st',
        'department' => 'CS',
        'phone' => '01012345678',
    ]);
    $registerResponse->assertStatus(201);
    $bobToken = $registerResponse->json('data.token');

    // Bob requests his profile on /me
    $meResponse = $this->withHeader('Authorization', 'Bearer '.$bobToken)
        ->getJson('/api/v1/me');

    $meResponse->assertOk();
    expect($meResponse->json('data.user.email'))->toBe('bob-registered@example.com');
    expect($meResponse->json('data.user.role'))->toBe('student');
});

it('prioritizes explicit Bearer token over ambient session cookie for a different user', function (): void {
    $admin = User::factory()->create(['email' => 'ambient-admin@example.com', 'password' => bcrypt('password123')]);
    $admin->assignRole('superadmin');

    $student = User::factory()->create(['email' => 'bearer-student@example.com']);
    $student->assignRole('student');
    $studentToken = $student->createToken('student-token')->plainTextToken;

    config([
        'sanctum.stateful' => ['localhost', '127.0.0.1'],
    ]);

    // Admin logs in creating session cookie
    $this->postJson('/api/v1/login', [
        'email' => 'ambient-admin@example.com',
        'password' => 'password123',
    ])->assertOk();

    // Request carries Bearer token for student while session has admin
    $response = $this->withHeader('Authorization', 'Bearer '.$studentToken)
        ->getJson('/api/v1/me');

    $response->assertOk();
    expect($response->json('data.user.email'))->toBe('bearer-student@example.com');
    expect($response->json('data.user.role'))->toBe('student');
});

it('rejects invalid Bearer token and does not fallback to ambient admin session cookie', function (): void {
    $admin = User::factory()->create(['email' => 'admin-cookie@example.com', 'password' => bcrypt('password123')]);
    $admin->assignRole('superadmin');

    config([
        'sanctum.stateful' => ['localhost', '127.0.0.1'],
    ]);

    // Admin logs in creating session cookie
    $this->postJson('/api/v1/login', [
        'email' => 'admin-cookie@example.com',
        'password' => 'password123',
    ])->assertOk();

    // Request sends an invalid Bearer token
    $response = $this->withHeader('Authorization', 'Bearer invalid-token-value')
        ->getJson('/api/v1/me');

    $response->assertUnauthorized();
});
