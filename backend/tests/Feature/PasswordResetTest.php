<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('sends a password reset link to existing user with configured frontend URL', function (): void {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'student@example.com',
    ]);

    $response = $this->postJson('/api/v1/forgot-password', [
        'email' => 'student@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'لو كان البريد الإلكتروني ده مسجل عندنا، هيوصلك رابط إعادة تعيين كلمة السر خلال دقائق.');

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $mail = $notification->toMail($user);
        $expectedPrefix = config('app.frontend_url').'/reset-password?token='.$notification->token;

        return str_starts_with($mail->actionUrl, $expectedPrefix)
            && str_contains($mail->actionUrl, 'email='.urlencode($user->email));
    });
});

it('handles forgot-password for non-existent email without 500 error', function (): void {
    $response = $this->postJson('/api/v1/forgot-password', [
        'email' => 'notfound@example.com',
    ]);

    $response->assertStatus(200);
});

it('validates email on forgot-password', function (): void {
    $response = $this->postJson('/api/v1/forgot-password', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('resets password successfully with valid token and email', function (): void {
    $user = User::factory()->create([
        'email' => 'student@example.com',
        'password' => Hash::make('OldPassword123!'),
    ]);

    $token = Password::broker()->createToken($user);

    $response = $this->postJson('/api/v1/reset-password', [
        'token' => $token,
        'email' => 'student@example.com',
        'password' => 'NewSecretPassword123!',
        'password_confirmation' => 'NewSecretPassword123!',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', __('passwords.reset'));

    $user->refresh();
    expect(Hash::check('NewSecretPassword123!', $user->password))->toBeTrue();
});

it('fails to reset password with invalid token', function (): void {
    $user = User::factory()->create([
        'email' => 'student@example.com',
        'password' => Hash::make('OldPassword123!'),
    ]);

    $response = $this->postJson('/api/v1/reset-password', [
        'token' => 'invalid-token',
        'email' => 'student@example.com',
        'password' => 'NewSecretPassword123!',
        'password_confirmation' => 'NewSecretPassword123!',
    ]);

    $response->assertStatus(400)
        ->assertJsonPath('message', __('passwords.token'));

    $user->refresh();
    expect(Hash::check('OldPassword123!', $user->password))->toBeTrue();
});

it('validates required fields and password confirmation on reset-password', function (): void {
    $response = $this->postJson('/api/v1/reset-password', [
        'token' => '',
        'email' => '',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token', 'email', 'password']);
});
