<?php

use App\Http\Controllers\ApiVerifyController;
use Illuminate\Support\Facades\Route;

Route::post('v1/verify/age', ApiVerifyController::class)
    ->middleware('throttle:30,1')
    ->name('api.v1.verify.age');
