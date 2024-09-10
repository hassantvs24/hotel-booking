<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\DeleteExpiredHotelRequests::class,
    ];
    /**
     * Register the commands for the application.
     *
     * @return void
     */

    protected function commands()
    {
        // Load commands from the 'Commands' directory
        $this->load(__DIR__ . '/Commands');

        // Include additional command files
        require base_path('routes/console.php');
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Schedule your commands here
        $schedule->command('app:delete-expired-hotel-requests')->everyMinute();
    }
}
