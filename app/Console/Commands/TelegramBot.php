<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Cache;

use App\Services\TelegramBotService;

use App\Jobs\TelegramBotGetUpdate;

class TelegramBot extends Command
{
    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TelegramBot:getUpDates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'TelegramBot getUpDates';

    private $service;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = new TelegramBotService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' START(' . date('Y-m-d H:i:s') . ') ', true);
        $bots = $this->service->bots();
        $cache_time = 0;
        $cache_start = microtime(true);
        $ok = true;
        foreach ($bots as $bot) {
            Cache::forget($bot['username']);
        }
        do {
            foreach ($bots as $bot) {
                dispatch(new TelegramBotGetUpdate($bot));
            }
            sleep(1);
            $live_end = microtime(true);
            if ($cache_time > 60) {
                foreach ($bots as $bot) {
                    Cache::forget($bot['username']);
                }
                $cache_time = 0;
                $cache_start = microtime(true);
            } else {
                $cache_time += ($live_end - $cache_start);
            }
        } while ($ok === true);
    }
}
