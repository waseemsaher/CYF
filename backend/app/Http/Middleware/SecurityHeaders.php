<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $connectOrigins = ["'self'"];

        $appUrl = (string) config('app.url', '');
        if ($appUrl !== '') {
            $scheme = parse_url($appUrl, PHP_URL_SCHEME);
            $host = parse_url($appUrl, PHP_URL_HOST);
            $port = parse_url($appUrl, PHP_URL_PORT);
            if (is_string($scheme) && is_string($host)) {
                $connectOrigins[] = $scheme.'://'.$host.($port ? ':'.$port : '');
            }
        }

        $frontendUrl = (string) config('app.frontend_url', '');
        if ($frontendUrl !== '') {
            $scheme = parse_url($frontendUrl, PHP_URL_SCHEME);
            $host = parse_url($frontendUrl, PHP_URL_HOST);
            $port = parse_url($frontendUrl, PHP_URL_PORT);
            if (is_string($scheme) && is_string($host)) {
                $connectOrigins[] = $scheme.'://'.$host.($port ? ':'.$port : '');
            }
        }

        if (app()->environment('local', 'testing')) {
            $connectOrigins[] = 'http://localhost:8000';
            $connectOrigins[] = 'http://127.0.0.1:8000';
            $connectOrigins[] = 'http://localhost:5173';
            $connectOrigins[] = 'http://localhost:4173';
        }

        $connectSrc = implode(' ', array_values(array_unique($connectOrigins)));

        $csp = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: blob:; media-src 'self'; connect-src {$connectSrc}; frame-ancestors 'self'; form-action 'self'";
        $response->headers->set('Content-Security-Policy', $csp);

        if ($request->isSecure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
