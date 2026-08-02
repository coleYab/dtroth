<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sandbox rate limiting
    |--------------------------------------------------------------------------
    |
    | Sandbox verification requests are limited per developer account per
    | minute to discourage abuse while keeping the tool responsive.
    |
    */

    'requests_per_minute' => (int) env('SANDBOX_REQUESTS_PER_MINUTE', 30),

];
