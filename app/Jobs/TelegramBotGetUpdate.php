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
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR bot : ' . var_export($this->bot, true));
            return;
        }
        $worker = Cache::get('worker:'.$this->bot['username']);
        if ( ! empty($worker)) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' worker : ' . var_export($worker, true));
            return;
        }
        Cache::put('worker:'.$this->bot['username'], json_encode($this->bot), 600);
        $this->service->logHead = ' ['.$this->bot['username'].'] - '.$this->bot['startAt'];
        $result = $this->service->readGetUpdates($this->bot['username'], 10, 30);
        $done = count($result);
        $this->bot['done'] += $done;
        $this->bot['endAt'] = date('Y-m-d H:i:s');
        if ( ! empty($done)) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' bot : ' . var_export($this->bot, true));
        }
        Cache::forget('worker:'.$this->bot['username']);
        dispatch(new TelegramBotGetUpdate($this->bot));
    }
}
