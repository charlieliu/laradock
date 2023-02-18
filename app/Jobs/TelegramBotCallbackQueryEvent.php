<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\TelegramBotService;
class TelegramBotCallbackQueryEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $service;
    private $callback = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($callback) {
        $this->service = new TelegramBotService;
        if ( ! empty($callback)) {
            $this->callback = $callback;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $this->service->logInfo(__METHOD__, 'LINE : '.__LINE__.' '.$this->service->logHead.' callback : '.var_export($this->callback, true));
        if ( ! empty($this->callback['bot_name'])) {
            $this->service->getToken($this->callback['bot_name']);
        }
        $this->service->callbackEvent($this->callback);
    }
}
