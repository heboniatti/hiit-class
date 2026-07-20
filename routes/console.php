<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keeps recurring classes stretching the same distance into the future. The
// command is idempotent, so a missed run costs nothing but a shorter horizon.
Schedule::command('classes:generate')->dailyAt('03:00');

// Reconciles the current month every day, so a missed scheduler run does not
// leave students without a fee. The command itself is idempotent.
Schedule::command('payments:generate')
    ->dailyAt('00:05')
    ->withoutOverlapping();
