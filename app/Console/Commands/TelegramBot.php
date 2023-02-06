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
        $live_start = microtime(true);
        $bots = $this->service->bots();
        $done = 0;
        $cache_time = 0;
        $cache_start = microtime(true);
        $ok = true;
        foreach ($bots as $bot) {
            Cache::forget($bot['username']);
        }
        do {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' START(' . date('Y-m-d H:i:s') . ') ', true);
            foreach ($bots as $bot) {
                if ( ! Cache::has($bot['username'])){
                    $time_start = microtime(true);
                    dispatch(new TelegramBotGetUpdate($bot));
                    $time_end = microtime(true);
                    $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Bot ['.$bot['username'].'] USED(' . ($time_end - $time_start) . ') ', true);
                }
            }
            sleep(1);
            $done++;
            $live_end = microtime(true);
            if ($cache_time > 600) {
                foreach ($bots as $bot) {
                    Cache::forget($bot['username']);
                }
                $cache_time = 0;
                $cache_start = microtime(true);
            } else {
                $cache_time += ($live_end - $cache_start);
            }
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Cache LIVE '. ($cache_time) . ' s', true);
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' DONE(' . $done . ') LIVE '. ($live_end - $live_start) . ' s', true);
        } while ($ok === true);
    }
}
