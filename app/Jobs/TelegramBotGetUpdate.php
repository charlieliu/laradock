<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

use App\Services\TelegramBotService;

class TelegramBotGetUpdate implements ShouldQueue, ShouldBeUnique
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
        $timeout = 5;
        $time_start = microtime(true);
        $this->service->logInfo(__METHOD__, 'START');
        if (empty($this->bot) || empty($this->bot['username'])) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR bot : ' . json_encode($this->bot), true);
            return;
        }

        $BotName = $this->bot['username'];
        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Bot ['.$BotName.'] START', true);
        $this->bot['startAt'] = date('Y-m-d H:i:s');

        $worker = Cache::get($BotName);
        if ( ! empty($worker)) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' worker ['.$BotName.'] '.var_export($worker, true), true);
            return;
        }

        Cache::put($BotName, json_encode($this->bot));

        $this->service->getToken($BotName);
        $rs = $this->service->runGetUpdates($BotName, $timeout);
        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' '.var_export($rs, true), true);

        if ($rs !== false && $rs->ok === true ) {

            $result = ! empty($rs->result) ? (array) $rs->result : [];

            $this->service->parseResult($result);

            foreach ($this->service->messages as $message) {
                $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' message : '.var_export($message, true), true);
                $this->messageEvent($message);
            }

            foreach ($this->service->new_chat_members as $chat_members) {
                foreach ($chat_members as $member) {
                    $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' new_chat_member : '.var_export($member, true), true);
                    $this->newChatMembersEvent($member);
                }
            }

            foreach ($this->service->callback_queries as $chat_callback) {
                foreach($chat_callback as $member_callback) {
                    foreach($member_callback as $callback) {
                        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' callback : '.var_export($callback, true), true);
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
        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' END');
    }

    public function messageEvent($message) {
        $this->service->logInfo(__METHOD__, 'START');
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
            // message from bot self
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' bot\'s message : ' . var_export($message, true));
            return;
        }
        if ($message['chat_id'] == $message['member_id']) {
            // message from private group
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' private group message : ' . var_export($message, true));
        }

        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' message : ' . var_export($message, true));

        $text = ! empty($message['text']) ? strtolower($message['text']) : '';
        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' text : ' . json_encode($text));

        if ( ! empty($text) && ! empty($message['chat_id'])) {

            $sendResult = false;

            if (strpos($text, 'are you bot') !== false ) {
                $sendResult = $this->service->sendMessage([
                    'chat_id' => $message['chat_id'],
                    'text' => 'Yes, @' . $message['bot_name'] . ' is a bot.'
                ]);
            }
            if ($text == '/start') {
                $sendResult = $this->service->sendMessage([
                    'chat_id' => $message['chat_id'],
                    'text' => '⚠️ This is the testnet version of @' . $message['bot_name'],
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

            if ( ! empty($sendResult) && $sendResult->isOk()) {
                $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Message sent to: ' . $message['chat_id']);
            } else {
                $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Sorry message not sent to: ' . $message['chat_id']);
            }
        }
        $this->service->logInfo(__METHOD__, 'END');
    }

    public function newChatMembersEvent($member) {
        $this->service->logInfo(__METHOD__, 'START');
        if (empty($member) || empty($member['id']) || empty($member['chat_id'])) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR member : ' . json_encode($member));
            return;
        }

        $this->service->logInfo(__METHOD__, 'member : ' . json_encode($member), true);

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
            'chat_id' => $member['chat_id'],
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text'=>'👉🏻解禁我👈🏻','callback_data'=>'unban_me']]],
            ])
        ];
        $sendResult = $this->service->sendMessage($data);
        if ($sendResult->isOk()) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Message sent to: ' . $member['chat_id']);
        } else {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Sorry message not sent to: ' . $member['chat_id']);
        }
        $this->service->logInfo(__METHOD__, 'END');
    }

    public function callbackEvent($callback) {
        $this->service->logInfo(__METHOD__, 'START');
        if (empty($callback)
            || empty($callback['chat_id'])
            || empty($callback['member_id'])
            || empty($callback['message_id'])
            || empty($callback['text'])) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR callback : ' . json_encode($callback), true);
            return;
        }

        $this->service->logInfo(__METHOD__, 'callback : ' . json_encode($callback), true);

        $data = strtolower($callback['text']);
        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' text : '.var_export($data, true), true);

        $text = '⚠️ This is the testnet version of @' . $this->bot['username'];
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
