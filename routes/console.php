<?php

use App\Console\Commands\CheckComponents;
use App\Console\Commands\ExpireConsents;
use App\Console\Commands\NotifyKeyExpiry;
use App\Console\Commands\NotifyUsageThresholds;
use App\Console\Commands\NotifyWebhookFailures;
use App\Console\Commands\RollupUsage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(RollupUsage::class)->dailyAt('00:30')->withoutOverlapping();
Schedule::command(CheckComponents::class)->everyFiveMinutes()->withoutOverlapping();
Schedule::command(ExpireConsents::class)->hourly()->withoutOverlapping();
Schedule::command(NotifyWebhookFailures::class)->dailyAt('08:00')->withoutOverlapping();
Schedule::command(NotifyKeyExpiry::class)->dailyAt('08:30')->withoutOverlapping();
Schedule::command(NotifyUsageThresholds::class)->hourlyAt(15)->withoutOverlapping();
