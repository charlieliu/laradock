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
    public function __construct($callback)
    {
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
    public function handle()
    {
        $this->service->logInfo(__METHOD__, 'START');
        if (empty($this->callback)
            || empty($this->callback['chat_id'])
            || empty($this->callback['member_id'])
            || empty($this->callback['message_id'])
            || empty($this->callback['text'])) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR callback : ' . json_encode($this->callback), true);
            return;
        }

        $callback = $this->callback;

        $this->service->logInfo(__METHOD__, 'callback : ' . json_encode($callback), true);

        $BotName = empty($this->callback['bot_name']) ? 'CharlieLiu_bot' : $callback['bot_name'];
        $this->service->getToken($BotName);

        $data = strtolower($this->callback['text']);
        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' text : '.var_export($data, true), true);

        $text = '⚠️ This is the testnet version of @' . $BotName;
        $reply_markup = json_encode([
            'inline_keyboard' => [
                [['text'=>'Back','callback_data'=>'/Start']]
            ],
        ]);
        switch ($data) {
            case '/start':
                $reply_markup = json_encode([
                    'inline_keyboard' => [
                        [['text'=>'Wallet','callback_data'=>'/wallet'],['text'=>'Subscriptions','callback_data'=>'/subscriptions']],
                        [['text'=>'Market','callback_data'=>'/market'],['text'=>'Exchange','callback_data'=>'/exchange']],
                        [['text'=>'Checks','callback_data'=>'/checks'],['text'=>'Invoices','callback_data'=>'/invoices']],
                        [['text'=>'Pay','callback_data'=>'/pay'],['text'=>'Contacts','callback_data'=>'/contacts']],
                        [['text'=>'Settings','callback_data'=>'/settings']]
                    ],
                ]);
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/wallet':
                $reply_markup = json_encode([
                    'inline_keyboard' => [
                        [['text'=>'Deposit','callback_data'=>'/deposit'],['text'=>'Withdraw','callback_data'=>'/withdraw']],
                        [['text'=>'Back','callback_data'=>'/Start']]
                    ],
                ]);
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/deposit':    // Wallet > Deposit
            case '/withdraw':   // Wallet > Withdraw
                $text = 'Please select a crypto currency.';
                $reply_markup = json_encode([
                    'inline_keyboard' => [
                        [['text'=>'BTC','callback_data'=>$data.'_btc']],
                        [['text'=>'ETH','callback_data'=>$data.'_eth']],
                        [['text'=>'Back','callback_data'=>'/Wallet']]
                    ],
                ]);
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/subscriptions':
                $text = 'Chat message subscription, not related to trading business.';
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/market':
                $reply_markup = json_encode([
                    'inline_keyboard' => [
                        [['text'=>'Buy','callback_data'=>'/Buy'],['text'=>'Sell','callback_data'=>'/Sell']],
                        [['text'=>'Back','callback_data'=>'/Start']]
                    ],
                ]);
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/buy':        // Market > Buy
            case '/sell':       // Market > Sell
                $text = 'Please select a crypto currency.';
                $reply_markup = json_encode([
                    'inline_keyboard' => [
                        [['text'=>'BTC','callback_data'=>$data.'_btc']],
                        [['text'=>'ETH','callback_data'=>$data.'_eth']],
                        [['text'=>'Back','callback_data'=>'/Market']]
                    ],
                ]);
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/pay':
                $text = 'Please select a crypto currency.';
                $reply_markup = json_encode([
                    'inline_keyboard' => [
                        [['text'=>'BTC','callback_data'=>$data.'_btc']],
                        [['text'=>'ETH','callback_data'=>$data.'_eth']],
                        [['text'=>'Back','callback_data'=>'/Start']]
                    ],
                ]);
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/exchange':
                $text = 'Support limit order trading, fee taker(0.75%) maker(0.5%)';
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/checks':
            case '/invoices':
            case '/contacts':
            case '/settings':
                $this->_editMessage($callback, $text, $reply_markup);
                break;
        }

        if ($data === 'unban_me') {
            $member = [
                'id'        => $callback['member_id'],
                'chat_id'   => $callback['chat_id'],
            ];
            $this->service->restrictChatMember($member, true);
            $this->service->logInfo(__METHOD__, 'member ' . json_encode($member));
            $this->service->deleteMessage([
                'chat_id'   => $callback['chat_id'],
                'message_id'=> $callback['message_id'],
            ]);
        }

        $this->service->logInfo(__METHOD__, 'END');
    }

    /**
     * edit Message text and keyboard
     *
     * @param array $callback
     * @param string $text
     * @param string $keyboard
     * @return void
     */
    private function _editMessage($callback, $text, $reply_markup = '') {
        if (empty($reply_markup)) {
            $reply_markup = json_encode([
                'inline_keyboard' => [
                    [['text'=>'Back','callback_data'=>'/Start']]
                ],
            ]);
        }
        if ($callback['message_text'] != $text) {
            $this->service->editMessageText([
                'chat_id' => $callback['chat_id'],
                'message_id' => $callback['message_id'],
                'text' => $text
            ]);
        }
        $this->service->editMessageReplyMarkup([
            'chat_id' => $callback['chat_id'],
            'message_id' => $callback['message_id'],
            'reply_markup' => $reply_markup
        ]);
    }
}
