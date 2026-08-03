<?php

use App\Http\Controllers\ApiVerifyController;
use App\Http\Controllers\ApiVerifySessionController;
use Illuminate\Support\Facades\Route;

Route::post('v1/verify/age', ApiVerifyController::class)
    ->middleware('throttle:30,1')
    ->name('api.v1.verify.age');

Route::post('v1/verify/session', ApiVerifySessionController::class)
    ->middleware('throttle:30,1')
    ->name('api.v1.verify.session');
