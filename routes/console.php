<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled Tasks ────────────────────────────────────────────────────────

// Data retention: purge old activity logs (>2 years) and notifications (>180 days)
// Runs daily at 02:00 AM to avoid peak-hour load.
Schedule::command('logs:purge')->dailyAt('02:00')->withoutOverlapping()->runInBackground();

// Horizon metrics snapshot — enables the Horizon metrics graphs in the dashboard
Schedule::command('horizon:snapshot')->everyFiveMinutes();
