<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\ProcessChefPayoutsJob;
use App\Models\ScheduledPayout;

Schedule::command('orders:send-scheduled-push')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Log::error('[ScheduledPush] Scheduler failed');
    });

// Run specifically on the 7th at 10:00 AM
Schedule::command('payout:auto-chefs')->monthlyOn(7, '10:00');

// Run specifically on the 22nd at 10:00 AM
Schedule::command('payout:auto-chefs')->monthlyOn(22, '10:00');

Schedule::command('orders:update-buffer-status')->everyMinute();

