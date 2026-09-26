<?php

declare(strict_types=1);

namespace App\Providers;

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

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return [
                Limit::perMinute(5)->by((string) $request->ip()),
                Limit::perMinute(5)->by($email !== '' ? Str::lower($email) : (string) $request->ip()),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(5)->by((string) $request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $email = (string) $request->input('email');

            return [
                Limit::perHour(3)->by((string) $request->ip()),
                Limit::perHour(3)->by($email !== '' ? Str::lower($email) : (string) $request->ip()),
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
