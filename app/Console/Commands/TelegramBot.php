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
            if ($rs === false) {
                $ok = false;
            }

            echo '任務完成 ' . date('Y-m-d H:i:s') . PHP_EOL;
        } while ($ok === true);
    }
}
