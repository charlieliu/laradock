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
        $this->service->logInfo(__METHOD__, 'START(' . date('Y-m-d H:i:s') . ') ', true);
        $live_start = microtime(true);
        $bots = $this->service->bots();
        $done = 0;
        $ok = true;
        do {
            foreach ($bots as $bot) {
                $time_start = microtime(true);

                if ($done < count($bots)) {
                    Cache::forget($bot['username']);
                }

                if ( ! Cache::has($bot['username'])){
                    $this->service->logInfo(__METHOD__, 'Bot ['.$bot['username'].'] START(' . date('Y-m-d H:i:s') . ') ', true);
                    dispatch(new TelegramBotGetUpdate($bot));

                    $time_end = microtime(true);
                    $this->service->logInfo(__METHOD__, 'Bot ['.$bot['username'].'] END(' . date('Y-m-d H:i:s') . ') ', true);
                    $this->service->logInfo(__METHOD__, 'Bot ['.$bot['username'].'] USED(' . ($time_end - $time_start) . ') ', true);
                    $done++;
                }
            }
            $live_end = microtime(true);
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' DONE(' . $done . ') LIVE '.($live_end - $live_start) . ' s', true);
        } while ($ok === true);
    }
}
