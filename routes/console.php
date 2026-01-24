<?php

use App\Jobs\UpdateDailyAnalytics;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process scheduled campaigns every minute
Schedule::command('campaigns:process-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Update daily analytics at midnight
Schedule::job(new UpdateDailyAnalytics())
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->runInBackground();
