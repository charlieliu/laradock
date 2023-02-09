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
        $timeout = 10;
        $limit = 100;
        if (empty($this->bot) || empty($this->bot['username'])) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR bot : ' . var_export($this->bot, true), true);
            return;
        }
        $this->service->logHead = ' ['.$this->bot['username'].']';
        $worker = Cache::get('worker:'.$this->bot['username']);
        if ( ! empty($worker)) {
            // $worker = json_decode($worker, true);
            // $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' worker exist', true);
            return;
        }
        $this->bot['startAt'] = date('Y-m-d H:i:s');
        $this->service->logHead = ' ['.$this->bot['username'].'] - '.$this->bot['startAt'];
        Cache::put('worker:'.$this->bot['username'], json_encode($this->bot));
        $this->service->readGetUpdates($this->bot['username'], $limit, $timeout);
        Cache::forget('worker:'.$this->bot['username']);
    }
}
