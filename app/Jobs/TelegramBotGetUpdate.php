<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Services\TelegramBotService;

class TelegramBotGetUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $service;
    private $bot = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($bot) {
        $this->service = new TelegramBotService;
        if ( ! empty($bot)) {
            $this->bot = $bot;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        if (empty($this->bot) || empty($this->bot['username'])) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR bot : ' . var_export($this->bot, true), true);
            return;
        }
        $worker = Cache::get('worker:'.$this->bot['username']);
        if ( ! empty($worker)) {
            $worker = is_array($worker) ? $worker : json_decode($worker, true);
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' EXIST worker : ' . var_export($worker, true), true);
            return;
        }
        $result = $this->service->readGetUpdates($this->bot['username'], 100, 60);
        $done = count($result);
        $this->bot['done'] += $done;
        $this->bot['endAt'] = date('Y-m-d H:i:s');
        if ( ! empty($done)) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' bot : ' . var_export($this->bot, true), true);
        }
        dispatch(new TelegramBotGetUpdate($this->bot));
    }
}
