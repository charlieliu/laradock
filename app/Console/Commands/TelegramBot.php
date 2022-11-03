<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;

use App\Services\TelegramBotService;

use App\Jobs\TelegramBotMessageEvent;
use App\Jobs\TelegramBotNewChatMembersEvent;
use App\Jobs\TelegramBotCallbackQueryEvent;

class TelegramBot extends Command
{
    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TelegramBot:getUpDates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'TelegramBot getUpDates';

    private $service;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = new TelegramBotService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $live_start = microtime(true);
        $BotName = 'CharlieLiu_bot';
        $this->service->getToken($BotName);
        $this->service->logInfo(__METHOD__, 'Bot ['.$BotName.'] START', true);
        $done = 0;
        $ok = true;
        do {
            $time_start = microtime(true);
            $rs = $this->service->runGetUpdates($BotName);

            if ($rs !== false && $rs->ok === true ) {
                $done++;

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

                foreach ($this->service->callback_queries as $callback) {
                    $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' callback : '.json_encode($callback), true);
                    dispatch(new TelegramBotCallbackQueryEvent($callback));
                }

                $time_end = microtime(true);
                $this->service->logInfo(__METHOD__, 'Bot ['.$BotName.'] DONE(' . $done . ')', true);
                $this->service->logInfo(__METHOD__, 'Bot ['.$BotName.'] USED '.($time_end - $time_start) . ' s', true);
                $this->service->logInfo(__METHOD__, 'Bot ['.$BotName.'] LIVE '.($time_end - $live_start) . ' s', true);
                // usleep( 100 );
            } else {
                $ok = false;
            }
        } while ($ok === true);

        $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Bot ['.$BotName.'] END(' . $done . ')', true);
    }
}
