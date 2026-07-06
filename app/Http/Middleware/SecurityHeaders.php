<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        $csp = "default-src 'self'; "
             . "script-src 'self' https://code.jquery.com https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; "
             . "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com 'unsafe-inline'; "
             . "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com data:; "
             . "img-src 'self' data: blob:; "
             . "connect-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
             . "form-action 'self'; "
             . "frame-ancestors 'none'; "
             . "base-uri 'self'; "
             . "object-src 'none';";
        $response->headers->set('Content-Security-Policy', $csp);

        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=(), usb=()');

        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');

        return $response;
    }
}
