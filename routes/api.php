<?php

use App\Http\Controllers\ApiConsentController;
use App\Http\Controllers\ApiVerificationLookupController;
use App\Http\Controllers\ApiVerifyController;
use App\Http\Controllers\ApiVerifySessionController;
use Illuminate\Support\Facades\Route;

Route::post('v1/verify/age', ApiVerifyController::class)
    ->middleware(['throttle:api', 'rate-limit-headers'])
    ->name('api.v1.verify.age');

Route::post('v1/verify/session', ApiVerifySessionController::class)
    ->middleware(['throttle:api', 'rate-limit-headers'])
    ->name('api.v1.verify.session');

Route::get('v1/verify/{query_id}', ApiVerificationLookupController::class)
    ->middleware(['throttle:api', 'rate-limit-headers'])
    ->name('api.v1.verify.show');

Route::post('v1/consents/{consent}/revoke', ApiConsentController::class)
    ->middleware(['throttle:api', 'rate-limit-headers'])
    ->name('api.v1.consents.revoke');
