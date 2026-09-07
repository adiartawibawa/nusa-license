<?php

use App\Console\Commands\AutoSuspendExpiredLicenseCommand;
use App\Console\Commands\SendExpiryReminderCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(AutoSuspendExpiredLicenseCommand::class)
    ->dailyAt('01:00')
    ->timezone('Asia/Makassar') // sesuaikan timezone operasional (WITA untuk Bali)
    ->withoutOverlapping()
    ->onOneServer()
    ->emailOutputOnFailure(config('mail.from.address'));

Schedule::command(SendExpiryReminderCommand::class)
    ->dailyAt('08:00') // pagi hari, waktu wajar untuk kirim reminder ke client
    ->timezone('Asia/Makassar')
    ->withoutOverlapping()
    ->onOneServer();
