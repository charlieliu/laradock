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
    public function handle() {
        $this->service->logInfo(__METHOD__, 'START');
        if (empty($this->message)) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR message : ' . var_export($this->message, true), true);
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
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' bot\'s message : ' . var_export($this->message, true));
            return;
        }
        if ($this->message['chat_id'] == $this->message['member_id']) {
            // message from private group
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' private group message : ' . var_export($this->message, true));
        }

        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' message : ' . var_export($this->message, true));

        $BotName = ! empty($this->message['bot_name']) ? $this->message['bot_name'] : 'CharlieLiu_bot';
        $this->service->getToken($BotName);

        $text = ! empty($this->message['text']) ? strtolower($this->message['text']) : '';
        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' text : ' . json_encode($text));

        if ( ! empty($text) && ! empty($this->message['chat_id'])) {

            $sendResult = false;

            if (strpos($text, 'are you bot') !== false ) {
                $sendResult = $this->service->sendMessage([
                    'chat_id' => $this->message['chat_id'],
                    'text' => 'Yes, @' . $BotName . ' is a bot.'
                ]);
            }
            if ($text == '/start') {
                $sendResult = $this->service->sendMessage([
                    'chat_id' => $this->message['chat_id'],
                    'text' => '⚠️ This is the testnet version of @' . $BotName,
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text'=>'Wallet','callback_data'=>'/Wallet'],['text'=>'Subscriptions','callback_data'=>'/Subscriptions']],
                            [['text'=>'Market','callback_data'=>'/Market'],['text'=>'Exchange','callback_data'=>'/Exchange']],
                            [['text'=>'Checks','callback_data'=>'/Checks'],['text'=>'Invoices','callback_data'=>'/Invoices']],
                            [['text'=>'Pay','callback_data'=>'/Pay'],['text'=>'Contacts','callback_data'=>'/Contacts']],
                            [['text'=>'Settings','callback_data'=>'/Settings']]
                        ],
                    ])
                ]);
            }

            if ( ! empty($sendResult) && $sendResult['ok'] === true) {
                $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Message sent to: ' . $this->message['chat_id']);
            } else {
                $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Sorry message not sent to: ' . $this->message['chat_id']);
            }
        }
        $this->service->logInfo(__METHOD__, 'END');
    }
}
