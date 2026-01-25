<?php

use App\Jobs\UpdateDailyAnalytics;
use App\Jobs\SendScheduledReportJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process scheduled campaigns every minute
Schedule::command('campaigns:process-scheduled')
    ->everyMinute()
    ->withoutOverlapping();

// Update daily analytics at midnight
Schedule::job(new UpdateDailyAnalytics())
    ->dailyAt('00:05')
    ->withoutOverlapping();

// Send weekly reports every Monday at 8:00 AM
Schedule::job(new SendScheduledReportJob('weekly'))
    ->weeklyOn(1, '08:00')
    ->withoutOverlapping();

// Send monthly reports on the 1st of each month at 8:00 AM
Schedule::job(new SendScheduledReportJob('monthly'))
    ->monthlyOn(1, '08:00')
    ->withoutOverlapping();

// Close previous month's analytics on the 1st of each month at 00:30
Schedule::command('sms:close-period')
    ->monthlyOn(1, '00:30')
    ->timezone('Africa/Libreville')
    ->withoutOverlapping();
