<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Billing Plans
    |--------------------------------------------------------------------------
    |
    | Plans are billed monthly in ETB through Chapa. Amounts are in minor
    | units (cents) to avoid floating point arithmetic.
    |
    */

    'currency' => 'ETB',

    'trial_days' => 14,

    'plans' => [
        'trial' => [
            'name' => 'Trial',
            'price_etb' => 0,
            'description' => 'Explore the platform for 14 days.',
            'calls_per_month' => 1000,
            'sort' => 0,
        ],
        'starter' => [
            'name' => 'Starter',
            'price_etb' => 49900,
            'description' => 'For small apps getting started with age verification.',
            'calls_per_month' => 50000,
            'sort' => 1,
        ],
        'pro' => [
            'name' => 'Pro',
            'price_etb' => 149900,
            'description' => 'For growing apps with higher verification volumes.',
            'calls_per_month' => 250000,
            'sort' => 2,
        ],
        'business' => [
            'name' => 'Business',
            'price_etb' => 399900,
            'description' => 'For high-volume platforms with dedicated support.',
            'calls_per_month' => 1000000,
            'sort' => 3,
        ],
    ],

    'overage_rate_per_1000_calls' => 300, // 300 cents = ETB 3.00

];
