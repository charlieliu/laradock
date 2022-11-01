<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * 你的應用程式提供的 Artisan 指令。
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\TelegramBot::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->command('TelegramBot:getUpDates')
        //     ->everyMinute()->before(function () {
        //         echo '任務將要開始...' . PHP_EOL;
        //     })
        //     ->after(function () {
        //         echo '任務已完成...' . PHP_EOL;
        //     });;
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
