<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

class TelegramBotController extends Controller
{
    private $service;

    public function __construct(TelegramBotService $telegramBotService)
    {
        $this->service = $telegramBotService;
    }

    /**
     * getUpdates and write to Mysql
     * @param string $name Bot username
     * @return string
     */
    public function run($name) {
        $BotName = '';
        $ApiKey = '';

        // check Bot exist
        foreach ($this->service->bots() as $row) {
            if ($row['username'] === $name) {
                $BotName = $row['username'];
                $ApiKey = $row['api_key'];
            }
        }

        // check Bot token
        if (empty($ApiKey)) {
            echo 'Bot ['.$name.'] not exist';
            exit;
        }

        try {
            // Create Telegram API object
            $telegram = new \Longman\TelegramBot\Telegram($ApiKey, $BotName);

            // Enable MySQL
            $telegram->enableMySql([
                'host'     => env('DB_HOST'),
                'port'     => env('DB_PORT'),
                'user'     => env('DB_USERNAME'),
                'database' => env('DB_DATABASE'),
                'password' => env('DB_PASSWORD')
            ]);

            // Handle telegram getUpdates request
            $telegram->useGetUpdatesWithoutDatabase(false);
            $rs = $telegram->handleGetUpdates();

            $columns = ['update_id'=>'ID','from'=>'From','type'=>'Type','date'=>'Date','text'=>'Text'];
            $result = ! empty($rs->result) ? (array) $rs->result : [];
            return view('content_list', [
                'active'    => 'tb',
                'title'     => 'Run Bot - '.$name,
                'columns'   => $columns,
                'list'      => $this->parse_list($result, $columns)
            ]);
        } catch (\Longman\TelegramBot\Exception\TelegramException $e) {
            echo $e->getMessage();
        }
    }

    public function bots() {
        $columns = ['id'=>'ID','username'=>'Bot Name','user_id'=>'User ID','first_name'=>'First Name','last_name'=>'Last Name','operations'=>'Operations'];
        $result = $this->service->bots();
        $btns = ['tg_detail'=>'username','tg_run'=>'username'];
        return view('content_list', [
            'active'    => 'tb_bots',
            'title'     => 'Telegram Bot List',
            'columns'   => $columns,
            'list'      => $this->parse_list($result, $columns, $btns)
        ]);
    }

    /**
     * cURL TelegramBot API getUpdates
     * @param string $name Bot username
     * @return string
     */
    public function read($name) {
        $result = $this->service->getUpdates($name);
        $columns = ['update_id'=>'ID','from'=>'From','type'=>'Type','date'=>'Date','text'=>'Text'];
        return view('content_list', [
            'active'    => 'tb_bots',
            'title'     => 'Bot - '.$name,
            'columns'   => $columns,
            'list'      => $this->parse_list($result, $columns)
        ]);
    }

    /**
     * Get chat List from Mysql
     * @return string
     */
    public function chats() {
        $result = $this->service->chats();
        $columns = ['id'=>'ID','title'=>'Title','type'=>'Type'];
        return view('content_list', [
            'active'    => 'tb_chats',
            'title'     => 'Telegram Chat List',
            'columns'   => $columns,
            'list'      => $this->parse_list($result, $columns)
        ]);
    }

    /**
     * Get user List from Mysql
     * @return string
     */
    public function users() {
        $result = $this->service->users();
        $columns = ['id'=>'ID','bot'=>'Is Bot','first_name'=>'First Name','last_name'=>'Last Name','username'=>'username'];
        return view('content_list', [
            'active'    => 'tb_users',
            'title'     => 'Telegram User List',
            'columns'   => $columns,
            'list'      => $this->parse_list($result, $columns)
        ]);
    }
}
