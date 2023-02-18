<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\TelegramBotService;
class TelegramBotMessageEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $service;
    private $message = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($message) {
        $this->service = new TelegramBotService;
        if ( ! empty($message)) {
            $this->message = $message;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $this->service->logInfo(__METHOD__, 'LINE : '.__LINE__.' '.$this->service->logHead.' message '.var_export($this->message, true));
        if ( ! empty($this->message['bot_name'])) {
            $this->service->getToken($this->message['bot_name']);
        }
        $this->service->messageEvent($this->message);
    }
}
