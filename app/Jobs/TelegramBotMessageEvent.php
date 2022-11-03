<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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
    public function __construct($message)
    {
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
    public function handle()
    {
        $this->service->logInfo(__METHOD__, 'START');
        if (empty($this->message)) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR message : ' . json_encode($this->message), true);
            return;
        }

        $bots = [];
        foreach ($this->service->bots() as $bot) {
            if (empty($bot['user_id'])) {
                continue;
            }
            $bots[$bot['user_id']] = $bot['username'];
        }

        if ( ! empty($bots[$this->message['member_id']])) {
            // message from bot self
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' bot\'s message : ' . json_encode($this->message));
            return;
        }
        if ($this->message['chat_id'] == $this->message['member_id']) {
            // message from private group
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' private group message : ' . json_encode($this->message));
            return;
        }

        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' message : ' . json_encode($this->message));

        $BotName = 'CharlieLiu_bot';
        $this->service->getToken($BotName);

        if ( ! empty($this->message['text']) &&  ! empty($this->message['chat_id'])) {
            if (strpos(strtolower($this->message['text']) , 'are you bot') !== false ) {
                $sendResult = $this->service->sendMessage([
                    'chat_id' => $this->message['chat_id'],
                    'text' => 'Yes, I am bot.'
                ]);
                // echo '$sendResult : ' . json_encode($sendResult) . PHP_EOL;
                if ($sendResult->isOk()) {
                    $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Message sent succesfully to: ' . $this->message['chat_id']);
                } else {
                    $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Sorry message not sent to: ' . $this->message['chat_id']);
                }
            }
        }
        $this->service->logInfo(__METHOD__, 'END');
    }
}
