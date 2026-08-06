<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds the X-RateLimit-* headers to API responses by reading the named
 * 'api' limiter's state for the current request. The middleware never
 * fails a request — if the limiter state cannot be computed the request
 * proceeds without headers.
 */
class EnsureRateLimitHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $limiter = RateLimiter::limiter('api');

            if ($limiter === null || $request->user() !== null) {
                return $response;
            }

            $limit = $limiter($request);

            if ($limit instanceof Limit && $limit->maxAttempts > 0) {
                $key = md5('api'.$request->ip());
                $maxAttempts = (int) $limit->maxAttempts;
                $remaining = max(0, $maxAttempts - RateLimiter::attempts($key));
                $reset = (int) (time() + RateLimiter::availableIn($key));

                $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
                $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
                $response->headers->set('X-RateLimit-Reset', (string) $reset);
            }
        } catch (\Throwable) {
            // Never fail the request over missing headers.
        }

        return $response;
    }
}
