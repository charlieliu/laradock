<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Log;
use App\Services\TelegramBotService;

class TelegramBot extends Command
{
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
    public function __construct(TelegramBotService $telegramBotService)
    {
        parent::__construct();
        $this->service = $telegramBotService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $BotName = 'CharlieLiu_bot';
        $bots = $this->service->bots();
        if (isset($bots[$BotName])) {
            $ApiKey = $bots[$BotName]['api_key'];
            $userID = $bots[$BotName]['user_id'];
        }

        // check Bot token
        if (empty($ApiKey)) {
            echo 'Bot ['.$BotName.'] not exist';
            exit;
        }

        echo 'Bot ['.$BotName.']' . PHP_EOL;
        $ok = true;
        do {
            echo '任務開始 ' . date('Y-m-d H:i:s') . PHP_EOL;

            $rs = $this->service->runGetUpdates($BotName);
            $ok = $rs === false ? false : $rs->ok;

            $result = ! empty($rs->result) ? (array) $rs->result : [];
            $this->service->parse_result($result);

            $sendMessage = [];
            // echo 'messages : ' . json_encode($this->service->messages) . PHP_EOL;
            foreach ($this->service->messages as $message) {
                echo 'message : ' . json_encode($message) . PHP_EOL;
                if ($userID == $message['member_id']) {
                    continue; // message from bot self
                }
                if ($message['chat_id'] == $message['member_id']) {
                    // message from bot self
                    continue;
                }
                if ( ! empty($message['text']) &&  ! empty($message['chat_id'])) {
                    echo 'strpos : ' . json_encode(strpos(strtolower($message['text']) , 'hi')) . PHP_EOL;
                    if (strpos(strtolower($message['text']) , 'hi') !== false ) {
                        $sendResult = $this->service->sendMessage([
                            'chat_id' => $message['chat_id'],
                            'text' => 'Hi I am bot.'
                        ]);
                        echo '$sendResult : ' . json_encode($sendResult) . PHP_EOL;
                        if ($sendResult->isOk()) {
                            $sendMessage[] = 'Message sent succesfully to: ' . $message['chat_id'];
                        } else {
                            $sendMessage[] = 'Sorry message not sent to: ' . $message['chat_id'];
                        }
                    }
                }
            }
            // echo 'sendMessage : ' . json_encode($sendMessage) . PHP_EOL;

            // echo 'polls : ' . json_encode($this->service->polls) . PHP_EOL;
            // echo 'members : ' . json_encode($this->service->members) . PHP_EOL;
            // echo 'new_chat_members : ' . json_encode($this->service->new_chat_members) . PHP_EOL;
            // echo 'left_chat_member : ' . json_encode($this->service->left_chat_member) . PHP_EOL;
            echo '任務完成 ' . date('Y-m-d H:i:s') . PHP_EOL;
        } while ($ok === true);
    }
}
