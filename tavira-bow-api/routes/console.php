<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes - Tavira BOW
|--------------------------------------------------------------------------
*/

// Inspirational command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Daily task reminders at 8:00 AM
Schedule::command('bow:send-task-reminders')
    ->dailyAt('08:00')
    ->description('Send task deadline reminders');

// Daily contract expiration alerts at 9:00 AM
Schedule::command('bow:send-contract-alerts')
    ->dailyAt('09:00')
    ->description('Send contract expiration alerts');

// Recalculate dashboard cache every hour
Schedule::command('bow:recalculate-dashboard')
    ->hourly()
    ->description('Recalculate dashboard statistics cache');

// Clean old activity logs monthly
Schedule::command('activitylog:clean')
    ->monthly()
    ->description('Clean old activity logs');
