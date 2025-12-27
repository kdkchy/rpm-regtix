<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command('app:send-email-campaign')->everyThreeMinutes();

// Cleanup unpaid registrations setiap jam
Schedule::command('app:cleanup-unpaid-registrations')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer(); // Jika menggunakan multiple servers

// Cleanup registration_failed yang sudah > 90 hari (harian)
Schedule::command('app:cleanup-old-registration-failed')
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();

// Poll email status from Brevo API every 30 minutes (fallback for webhook)
Schedule::command('app:check-email-status')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer();
