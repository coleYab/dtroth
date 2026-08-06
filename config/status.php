<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Status page components
    |--------------------------------------------------------------------------
    |
    | Each entry is a service the status page reports on. Checks run on a
    | schedule (see the status:check command) and are stored in the
    | status_checks table rather than measured on every page load, keeping
    | the status page fast and available even when other parts of the app
    | are struggling.
    |
    */

    'components' => [
        [
            'key' => 'verification-api',
            'label' => 'Verification API',
            'url' => env('STATUS_CHECK_VERIFICATION_API_URL', '/api/v1/verify/age'),
            'expected_status' => 401,
            'description' => 'POST /v1/verify/age and related endpoints',
        ],
        [
            'key' => 'identity-provider',
            'label' => 'Identity Provider',
            'url' => env('STATUS_CHECK_IDENTITY_PROVIDER_URL', '/up'),
            'expected_status' => 200,
            'description' => 'National ID verification backend',
        ],
        [
            'key' => 'webhook-delivery',
            'label' => 'Webhook Delivery',
            'url' => env('STATUS_CHECK_WEBHOOK_URL', '/up'),
            'expected_status' => 200,
            'description' => 'Queued delivery of signed webhook events',
        ],
        [
            'key' => 'oauth-oidc',
            'label' => 'OAuth / OIDC Service',
            'url' => env('STATUS_CHECK_OAUTH_URL', '/login'),
            'expected_status' => 200,
            'description' => 'Authorization server and token endpoints',
        ],
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'url' => env('STATUS_CHECK_DASHBOARD_URL', '/login'),
            'expected_status' => 200,
            'description' => 'Developer dashboard and billing pages',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Check thresholds
    |--------------------------------------------------------------------------
    |
    | A component is degraded when it responds slower than degraded_above_ms
    | and down when it fails, times out, or responds with an unexpected
    | status code.
    |
    */

    'degraded_above_ms' => (int) env('STATUS_DEGRADED_ABOVE_MS', 3000),

    'timeout_ms' => (int) env('STATUS_CHECK_TIMEOUT_MS', 5000),

];
