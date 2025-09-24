<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Profile;
use App\Jobs\ScrapeProfileJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
         // Scrape profiles with >100k likes every 24h
    $schedule->call(function () {
        $profiles = Profile::where('likes', '>', 100000)->pluck('username');
        foreach ($profiles as $username) {
            ScrapeProfileJob::dispatch($username)->onQueue('jobs');
        }
    })->daily();

    // Scrape other profiles every 72h
    $schedule->call(function () {
        $profiles = Profile::where('likes', '<=', 100000)->pluck('username');
        foreach ($profiles as $username) {
            ScrapeProfileJob::dispatch($username)->onQueue('jobs');
        }
    })->cron('0 0 */3 * *');

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
