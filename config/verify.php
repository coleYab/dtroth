<?php

use App\Providers\Identity\MockFaydaProvider;
use App\Providers\Identity\SandboxIdentityProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Identity provider selection
    |--------------------------------------------------------------------------
    |
    | The provider resolved for production environments. Sandbox environments
    | always use the sandbox adapter, so switching this value only affects
    | live verification behavior.
    |
    | Supported values:
    |   - 'mock_fayda' — the simulated national ID provider
    |   - 'sandbox'    — resolve production against mock test identities
    |
    */

    'provider' => env('VERIFY_PROVIDER', 'mock_fayda'),

    /*
    |--------------------------------------------------------------------------
    | API rate limit
    |--------------------------------------------------------------------------
    |
    | The named 'api' limiter (throttle:api) caps requests per IP per minute
    | across all public endpoints. Limits are surfaced to callers through the
    | X-RateLimit-* response headers.
    |
    */

    'api_requests_per_minute' => (int) env('VERIFY_API_REQUESTS_PER_MINUTE', 60),

    /*
    |--------------------------------------------------------------------------
    | Usage notification thresholds
    |--------------------------------------------------------------------------
    |
    | Percentages of the plan's monthly call quota at which the
    | notify:usage-thresholds command emails the team. Percentages are
    | evaluated against the trailing 30 days of usage rollups.
    |
    */

    'usage_thresholds' => [80, 100],

    /*
    |--------------------------------------------------------------------------
    | Provider registry
    |--------------------------------------------------------------------------
    |
    | Every provider available in the system. The 'class' entry must
    | implement App\Contracts\IdentityProvider. Provider-specific options are
    | read at runtime so tests can override them per request.
    |
    */

    'providers' => [
        'mock_fayda' => [
            'class' => MockFaydaProvider::class,
            'latency_min_ms' => (int) env('VERIFY_PROVIDER_LATENCY_MIN', 40),
            'latency_max_ms' => (int) env('VERIFY_PROVIDER_LATENCY_MAX', 120),
            'timeout_ms' => (int) env('VERIFY_PROVIDER_TIMEOUT_MS', 1000),
            'failure_rate' => (float) env('VERIFY_PROVIDER_FAILURE_RATE', 0.0),
        ],
        'sandbox' => [
            'class' => SandboxIdentityProvider::class,
        ],
    ],

];
