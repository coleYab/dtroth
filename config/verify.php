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
