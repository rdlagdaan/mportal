<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // 🔥 Event lifecycle scheduler
        $schedule->command('events:check-notifications')->everyMinute();

        // (optional) keep if needed
        $schedule->command('notifications:send-scheduled')->everyMinute();

        // $schedule->command('dtr:timeout-reminder')->everyMinute();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}