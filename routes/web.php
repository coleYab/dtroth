<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::inertia('legal/terms', 'legal/terms')->name('legal.terms');
Route::inertia('legal/privacy-policy', 'legal/privacy-policy')->name('legal.privacy-policy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
