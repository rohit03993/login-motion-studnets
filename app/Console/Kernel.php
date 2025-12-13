<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Option 1: Real-time watcher (recommended - runs continuously, checks every 10 seconds)
        // Run this manually: php artisan punch:watch
        // Or use: .\watch-punches.ps1
        
        // Option 2: Scheduled command (runs every minute, checks last 5 minutes)
        // This is a fallback if the watcher is not running
        $schedule->command('punch:notify --minutes=5')->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Note: the force-in command is for manual triggering only (no schedule)
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

