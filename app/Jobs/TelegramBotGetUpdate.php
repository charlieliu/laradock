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
    public function __construct($bot)
    {
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
        $time_start = microtime(true);
        $this->service->logInfo(__METHOD__, 'START');
        if (empty($this->bot) || empty($this->bot['username'])) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR callback : ' . json_encode($this->bot), true);
            return;
        }

        $BotName = $this->bot['username'];
        $this->service->logInfo(__METHOD__, 'Bot ['.$BotName.'] START', true);

        $expiresAt = $time_start + $timeout;
        Cache::put($BotName, $BotName, date('Y-m-d H:i:s', $expiresAt));

        $this->service->getToken($BotName);
        $rs = $this->service->runGetUpdates($BotName, $timeout);
        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' '.var_export($rs, true), true);

        if ($rs !== false && $rs->ok === true ) {

            $result = ! empty($rs->result) ? (array) $rs->result : [];

            $this->service->parseResult($result);

            foreach ($this->service->messages as $message) {
                $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' message : '.json_encode($message), true);
                dispatch(new TelegramBotMessageEvent($message));
            }

            foreach ($this->service->new_chat_members as $chat_members) {
                foreach ($chat_members as $member) {
                    $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' new_chat_member : '.json_encode($member), true);
                    dispatch(new TelegramBotNewChatMembersEvent($member));
                }
            }

            foreach ($this->service->callback_queries as $chat_callback) {
                foreach($chat_callback as $member_callback){
                    foreach($member_callback as $callback){
                        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' callback : '.var_export($callback, true), true);
                        dispatch(new TelegramBotCallbackQueryEvent($callback));
                    }
                }
            }

            $time_end = microtime(true);
            $this->service->logInfo(__METHOD__, 'Bot ['.$BotName.'] USED '.($time_end - $time_start) . ' s', true);
        } else {
            $time_end = microtime(true);
            $this->service->logInfo(__METHOD__, 'Bot ['.$BotName.'] ERROR USED '.($time_end - $time_start) . ' s', true);
        }

        Cache::forget($BotName);
        $this->service->logInfo(__METHOD__, 'END');
    }
}
