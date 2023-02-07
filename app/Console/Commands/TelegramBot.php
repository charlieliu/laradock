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
        foreach ($bots as $bot) {
            Cache::forget($bot['username']);
        }
        $ok = true;
        do {
            foreach ($bots as $bot) {
                $worker = Cache::get($bot['username']);
                if ( ! empty($worker)) {
                    // $worker = json_decode($worker, true);
                    // $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ['.$bot['username'].'] worker start at '.var_export($worker['startAt'], true), true);
                    continue;
                }
                dispatch(new TelegramBotGetUpdate($bot));
            }
            sleep(1);
        } while ($ok === true);
    }
}
