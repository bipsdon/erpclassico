<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled tasks ────────────────────────────────────────

// Delete uploaded profile photos for orders older than 3 months.
// Runs once a day at 02:00 (off-peak).
Schedule::command('orders:prune-profile-photos')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();
