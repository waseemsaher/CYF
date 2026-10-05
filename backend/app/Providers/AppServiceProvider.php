<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->environment('production'));

        ResetPassword::createUrlUsing(function ($notifiable, string $token): string {
            $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/');

            return "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode($notifiable->getEmailForPasswordReset());
        });

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));
            $emailIpKey = $email !== '' ? "{$email}|".$request->ip() : (string) $request->ip();

            return [
                Limit::perMinute(100)->by((string) $request->ip()),
                Limit::perMinute(5)->by($emailIpKey),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(30)->by((string) $request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));
            $emailIpKey = $email !== '' ? "{$email}|".$request->ip() : (string) $request->ip();

            return [
                Limit::perMinute(60)->by((string) $request->ip()),
                Limit::perMinute(5)->by($emailIpKey),
            ];
        });

        RateLimiter::for('payment-submit', function (Request $request) {
            return Limit::perHour(10)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('telegram-webhook', function (Request $request) {
            return Limit::perMinute(300)->by((string) $request->ip());
        });
    }
}
