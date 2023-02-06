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
        $timeout = 30;
        $time_start = microtime(true);
        if (empty($this->bot) || empty($this->bot['username'])) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR bot : ' . var_export($this->bot, true), true);
            return;
        }
        $BotName = $this->bot['username'];
        $this->bot['startAt'] = date('Y-m-d H:i:s');
        $worker = Cache::get($BotName);
        if ( ! empty($worker)) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' worker ['.$BotName.'] '.var_export($worker, true), true);
            return;
        }
        Cache::put($BotName, json_encode($this->bot));
        $this->service->getToken($BotName);
        $rs = $this->service->runGetUpdates($BotName, $timeout);
        if ($rs !== false && $rs->ok === true ) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' '.var_export($rs, true), true);
            $result = ! empty($rs->result) ? (array) $rs->result : [];
            $this->service->parseResult($result);
            foreach ($this->service->messages as $message) {
                $this->messageEvent($message);
            }
            foreach ($this->service->new_chat_members as $chat_members) {
                foreach ($chat_members as $member) {
                    $this->newChatMembersEvent($member);
                }
            }
            foreach ($this->service->callback_queries as $chat_callback) {
                foreach ($chat_callback as $member_callback) {
                    foreach ($member_callback as $callback) {
                        $this->callbackEvent($callback);
                    }
                }
            }
            $time_end = microtime(true);
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Bot ['.$BotName.'] USED '.($time_end - $time_start) . ' s', true);
        } else {
            $time_end = microtime(true);
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Bot ['.$BotName.'] ERROR USED '.($time_end - $time_start) . ' s', true);
        }
        Cache::forget($BotName);
    }

    public function messageEvent($message) {
        if (empty($message)) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR message : ' . var_export($message, true), true);
            return;
        }
        $bots = [];
        foreach ($this->service->bots() as $bot) {
            if (empty($bot['user_id'])) {
                continue;
            }
            $bots[$bot['user_id']] = $bot['username'];
        }
        if ( ! empty($bots[$message['member_id']])) {
            // message from bots
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' bot\'s message : ' . var_export($message, true));
            return;
        }
        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' message : ' . var_export($message, true));
        $text = ! empty($message['text']) ? strtolower($message['text']) : '';
        if ( ! empty($text) && ! empty($message['chat_id'])) {
            if (strpos($text, 'are you bot') !== false ) {
                $this->service->sendMessage([
                    'chat_id' => $message['chat_id'],
                    'text' => 'Yes, @' . $this->bot['username'] . ' is a bot.'
                ]);
            }
            if ($message['chat_id'] == $message['member_id'] && $text == '/start') {
                // message from private group
                $message_text  = 'Buy, sell, store and pay with cryptocurrency whenever you want.';
                $message_text .= '\n\n';
                $message_text .= '⚠️ This is the testnet version of @' . $this->bot['username'];
                $this->service->sendMessage([
                    'chat_id'       => $message['chat_id'],
                    'text'          => $message_text,
                    'parse_mode'    => 'HTML',
                    'reply_markup'  => json_encode([
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
        }
    }

    public function newChatMembersEvent($member) {
        if (empty($member) || empty($member['id']) || empty($member['chat_id'])) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR member : ' . var_export($member, true));
            return;
        }
        $bots = [];
        foreach ($this->service->bots() as $bot) {
            if (empty($bot['user_id'])) {
                continue;
            }
            $bots[$bot['user_id']] = $bot['username'];
        }
        if ( ! empty($bots[$member['id']])) {
            // event from bots
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' bot\'s event : ' . var_export($member, true));
            return;
        }
        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' member : ' . var_export($member, true), true);
        // set all permissions false
        $this->service->restrictChatMember($member, false);
        $text = '';
        if ( ! empty($member['first_name'])) {
            $text .= $member['first_name'].' ';
        } else if ( ! empty($member['username'])) {
            $text .= '@'.$member['username'].' ';
        }
        $text .= '歡迎到 本社群，請維持禮貌和群友討論，謝謝！\n';
        $text .= '進到群組請先觀看我們的群組導航，裡面可以解決你大多數的問題\n';
        $text .= '\n\n';
        $text .= '新進來的朋友記得點一下 “👉🏻解禁我👈🏻”\n';
        $text .= '來不及點到的、無法發言的，請退群重加';
        $data = [
            'chat_id'       => $member['chat_id'],
            'text'          => $text,
            'parse_mode'    => 'HTML',
            'reply_markup'  => json_encode([
                'inline_keyboard' => [[['text'=>'👉🏻解禁我👈🏻','callback_data'=>'/un_mute:'.$member['id']]]],
            ])
        ];
        $sendResult = $this->service->sendMessage($data);
        if ( ! empty($sendResult) && $sendResult['ok'] === true) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Message sent to: ' . $member['chat_id']);
        } else {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Sorry message not sent to: ' . $member['chat_id']);
        }
    }

    public function callbackEvent($callback) {
        if (empty($callback)
            || empty($callback['chat_id'])
            || empty($callback['member_id'])
            || empty($callback['message_id'])
            || empty($callback['text'])) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR callback : ' . var_export($callback, true), true);
            return;
        }
        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' callback : ' . var_export($callback, true), true);
        $data = strtolower($callback['text']);
        $text  = 'Buy, sell, store and pay with cryptocurrency whenever you want.\n';
        $text .= '\n';
        $text .= '⚠️ This is the testnet version of @' . $this->bot['username'];
        $reply_markup = json_encode([
            'inline_keyboard' => [
                [['text'=>'Back','callback_data'=>'/Start']]
            ],
        ]);
        if (strpos($data, '/un_mute') !== false) {
            $explode = explode(':', $data);
            $data = $explode[0];
            $from_user = (int) $explode[1];
        }
        switch ($data) {
            case '/un_mute':
                if ($from_user == $callback['member_id']) {
                    $this->service->restrictChatMember([
                        'id'        => $callback['member_id'],
                        'chat_id'   => $callback['chat_id'],
                    ], true);
                    $this->service->deleteMessage([
                        'chat_id'   => $callback['chat_id'],
                        'message_id'=> $callback['message_id'],
                    ]);
                }
                break;
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
                $text  = '🐬 Here you can exchange cryptocurrencies using limit orders that executed automatically.';
                $text .= '\n\n';
                $text .= '🪐 Create your order to start. 0.75% fee for takers and 0.5% fee for makers.';
                $reply_markup = json_encode([
                    'inline_keyboard' => [
                        [['text'=>'Exchange Now','callback_data'=>'/do_exchange']],
                        [['text'=>'Order History','callback_data'=>'/order_history']],
                        [['text'=>'Back','callback_data'=>'/Start']]
                    ],
                ]);
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/do_exchange': // Exchange > Exchange Now
                $text  = 'Choose cryptocurrencies you want to exchange\.';
                $reply_markup = json_encode([
                    'inline_keyboard' => [
                        [['text'=>'BTC\/USDT','callback_data'=>$data.'_btc']],
                        [['text'=>'ETH\/USDT','callback_data'=>$data.'_eth']],
                        [['text'=>'Back','callback_data'=>'/exchange']]
                    ],
                ]);
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/checks':
            case '/invoices':
            case '/contacts':
            case '/settings':
                $this->_editMessage($callback, $text, $reply_markup);
                break;
        }
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
        if ($callback['message_text'] != $text) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' editMessageText callback : ' . var_export($callback, true), true);
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' editMessageText text : ' . var_export($text, true), true);
            $data = [
                'chat_id'       => $callback['chat_id'],
                'message_id'    => $callback['message_id'],
                'text'          => $text,
                'parse_mode'    => 'HTML'
            ];
            if ( ! empty($reply_markup)) {
                $data['reply_markup'] = $reply_markup;
            }
            $this->service->editMessageText($data);
        } else if ( ! empty($reply_markup)) {
            $this->service->editMessageReplyMarkup([
                'chat_id'       => $callback['chat_id'],
                'message_id'    => $callback['message_id'],
                'reply_markup'  => $reply_markup
            ]);
        }
    }
}
