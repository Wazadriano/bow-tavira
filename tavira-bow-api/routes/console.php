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

// Daily task reminders at 8:00 AM (J-14 and Jour J)
Schedule::command('bow:send-task-reminders')
    ->dailyAt('08:00')
    ->description('Send task deadline reminders (J-14 and Jour J)');

// Daily overdue notifications at 8:30 AM (J+1: team leads + Ranjit)
Schedule::command('bow:send-overdue-notifications')
    ->dailyAt('08:30')
    ->description('Notify team leads of overdue tasks (J+1)');

// Weekly CEO report every Monday at 6:00 AM
Schedule::command('bow:send-weekly-ceo-report')
    ->weeklyOn(1, '06:00')
    ->description('Send weekly consolidated report to CEO');

// Daily contract expiration alerts at 9:00 AM
Schedule::command('bow:send-contract-alerts')
    ->dailyAt('09:00')
    ->description('Send contract expiration alerts');

// Recalculate dashboard cache every hour
Schedule::command('bow:recalculate-dashboard')
    ->hourly()
    ->description('Recalculate dashboard statistics cache');

// Daily summary email to admins/managers at 7:00 AM
Schedule::command('bow:send-daily-summary')
    ->dailyAt('07:00')
    ->description('Send daily summary email to admins and managers');

// Clean old activity logs monthly
Schedule::command('activitylog:clean')
    ->monthly()
    ->description('Clean old activity logs');

// Database backup daily at 02:00 AM
Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->description('Run daily database backup');

// Full backup weekly on Sunday at 03:00 AM
Schedule::command('backup:run')
    ->weeklyOn(0, '03:00')
    ->description('Run weekly full backup');

// Clean old backups weekly
Schedule::command('backup:clean')
    ->weeklyOn(0, '04:00')
    ->description('Clean old backups');

// Monitor backup health daily
Schedule::command('backup:monitor')
    ->dailyAt('06:00')
    ->description('Monitor backup health');
