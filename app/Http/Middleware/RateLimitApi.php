<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $maxAttempts = $request->isMethod('GET') ? 120 : 60;
        $decaySeconds = 60;

        $key = 'api:' . ($request->user()?->id ?? $request->ip());

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $message = 'Terlalu banyak permintaan. Silakan coba lagi dalam ' . ceil($seconds / 60) . ' menit.';

            if ($request->expectsJson() || $request->isXmlHttpRequest()) {
                return response()->json(['error' => $message], 429);
            }

            return redirect()->back()->with('error', $message);
        }

        RateLimiter::hit($key, $decaySeconds);

        return $next($request);
    }
}
