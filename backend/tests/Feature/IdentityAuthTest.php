<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('registers a new student and returns a token', function (): void {
    Role::create(['name' => 'student']);
    $year = AcademicYear::create(['name' => ['ar' => 'السنة الأولى', 'en' => '1st Year'], 'sort_order' => 1]);

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Test Student',
        'email' => 'student@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'branch' => 'azhar_boys',
        'academic_year_id' => $year->id,
        'department' => 'CS',
        'phone' => '01012345678',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.user.email', 'student@example.com')
        ->assertJsonPath('data.user.role', 'student')
        ->assertJsonPath('data.user.academic_year_id', $year->id)
        ->assertJsonPath('data.user.telegram_is_linked', false);

    $this->assertDatabaseHas('users', [
        'email' => 'student@example.com',
        'phone' => '01012345678',
        'academic_year_id' => $year->id,
    ]);
});

it('validates mandatory phone number and format on registration', function (): void {
    Role::create(['name' => 'student']);
    $year = AcademicYear::create(['name' => ['ar' => 'السنة الأولى', 'en' => '1st Year'], 'sort_order' => 1]);

    $missingPhone = $this->postJson('/api/v1/register', [
        'name' => 'No Phone',
        'email' => 'nophone@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'branch' => 'azhar_boys',
        'academic_year_id' => $year->id,
        'department' => 'CS',
    ]);

    $missingPhone->assertStatus(422)
        ->assertJsonValidationErrors(['phone'])
        ->assertJsonPath('errors.phone.0', 'رقم محفظتك الإلكترونية مطلوب لاسترداد أي مبلغ عند الحاجة.');

    $invalidPhone = $this->postJson('/api/v1/register', [
        'name' => 'Invalid Phone',
        'email' => 'invalidphone@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'branch' => 'azhar_boys',
        'academic_year_id' => $year->id,
        'department' => 'CS',
        'phone' => '01312345678',
    ]);

    $invalidPhone->assertStatus(422)
        ->assertJsonValidationErrors(['phone'])
        ->assertJsonPath('errors.phone.0', 'رقم المحفظة يجب أن يكون رقم موبايل مصري صحيح مكوّن من 11 رقمًا (مثال: 01012345678).');
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

it('prevents deactivated user from logging in', function (): void {
    Role::create(['name' => 'student']);

    $user = User::factory()->create([
        'email' => 'deactivated@example.com',
        'password' => bcrypt('Password123!'),
        'is_active' => false,
    ]);

    $user->assignRole('student');

    $response = $this->postJson('/api/v1/login', [
        'email' => 'deactivated@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('message', 'This account has been deactivated.');
});

it('authenticates subsequent request via Sanctum SPA session cookie across codeera.tech subdomains', function (): void {
    Role::create(['name' => 'student']);

    $user = User::factory()->create([
        'email' => 'spa-student@example.com',
        'password' => bcrypt('SecretPass123!'),
    ]);
    $user->assignRole('student');

    config([
        'sanctum.stateful' => ['codeera.tech', 'app.codeera.tech'],
        'session.domain' => '.codeera.tech',
        'session.secure' => true,
    ]);

    $originHeaders = [
        'Origin' => 'https://app.codeera.tech',
        'Referer' => 'https://app.codeera.tech/',
    ];

    $loginResponse = $this->withHeaders($originHeaders)->postJson('/api/v1/login', [
        'email' => 'spa-student@example.com',
        'password' => 'SecretPass123!',
    ]);

    $loginResponse->assertStatus(200);

    $sessionCookieName = config('session.cookie');
    $cookies = $loginResponse->headers->getCookies();
    $foundSessionCookie = null;
    foreach ($cookies as $cookie) {
        if ($cookie->getName() === $sessionCookieName) {
            $foundSessionCookie = $cookie;
            break;
        }
    }

    expect($foundSessionCookie)->not->toBeNull();

    // Subsequent request to protected route without Bearer token, relying strictly on session cookie
    $meResponse = $this->withHeaders($originHeaders)
        ->withCookie($sessionCookieName, $foundSessionCookie->getValue())
        ->getJson('/api/v1/me');

    $meResponse->assertStatus(200)
        ->assertJsonPath('data.user.email', 'spa-student@example.com');
});

it('logs out authenticated user and revokes the current access token', function (): void {
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

    $user = User::factory()->create([
        'email' => 'logout-student@example.com',
    ]);
    $user->assignRole('student');

    $token = $user->createToken('auth-token');

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/logout');

    $response->assertStatus(200)
        ->assertJsonPath('message', 'تم تسجيل الخروج بنجاح.');

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->id,
    ]);

    app('auth')->forgetGuards();

    $subsequentResponse = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson('/api/v1/me');

    $subsequentResponse->assertStatus(401);
});

it('requires authentication for logout endpoint', function (): void {
    $response = $this->postJson('/api/v1/logout');

    $response->assertStatus(401);
});
