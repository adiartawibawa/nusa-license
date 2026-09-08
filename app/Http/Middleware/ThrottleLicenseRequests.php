<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleLicenseRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $licenseKey = $request->input('license_key', $request->ip());
        $key = "license-verify:{$licenseKey}";

        $maxAttempts = config('nusalicense.throttle.max_attempts');
        $decaySeconds = config('nusalicense.throttle.decay_seconds');

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'status' => 'rate_limited',
                'message' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ], 429)->header('Retry-After', $seconds);
        }

        RateLimiter::hit($key, $decaySeconds);

        return $next($request);
    }
}
