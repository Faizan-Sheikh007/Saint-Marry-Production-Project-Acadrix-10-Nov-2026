<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline hardening headers on every response — none of this replaces
 * HTTPS itself (see AppServiceProvider::boot(), which forces the https://
 * scheme in production), but together they close off the common ways a
 * page like this gets abused: being framed by another site (clickjacking),
 * a browser guessing a response's content type, leaking the full referrer
 * URL to third-party resources, and downgrade attacks once HTTPS is live.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        if ($request->isSecure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (\Illuminate\Support\Facades\Auth::guard('web')->check()
            || \Illuminate\Support\Facades\Auth::guard('accounts')->check()) {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        }

        return $response;
    }
}
