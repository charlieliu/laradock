<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;

class TelegramBotController extends Controller
{
    public $breadcrumbs = [];
    private $service;

    public function __construct(TelegramBotService $telegramBotService)
    {
        $this->service = $telegramBotService;
    }

    /**
     * Telegram Bot list from Mysql
     * @return string
     */
    public function bots() {
        $this->breadcrumbs = ['Telegram Bots'=>'/tb'];
        $columns = [
            'id'            => 'ID',
            'username'      => 'Bot Name',
            'user_id'       => 'User ID',
            'first_name'    => 'First Name',
            'last_name'     => 'Last Name',
            'operations'    => 'Operations'
        ];
        $result = $this->service->bots();
        $btns = [
            'tg_detail' => 'username',
            'tg_run'    => 'username'
        ];
        return view('content_list', [
            'active'        => 'tb_bots',
            'title'         => 'Telegram Bot List',
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns, $btns)
        ]);
    }

    /**
     * getUpdates and write to Mysql
     * @param string $name Bot username
     * @return string
     */
    public function run($name) {
        $this->breadcrumbs = ['Telegram Bots'=>'/tb',$name.' Run'=>'/tb/run/'.$name];
        $BotName = '';
        $ApiKey = '';

        // check Bot exist
        $bots = $this->service->bots();
        if (isset($bots[$name])) {
            $BotName = $bots[$name]['username'];
            $ApiKey = $bots[$name]['api_key'];
        }

        // check Bot token
        if (empty($ApiKey)) {
            echo 'Bot ['.$name.'] not exist';
            exit;
        }

        $rs = $this->service->runGetUpdates($BotName);
        $result = isset($rs->result) ? (array) $rs->result : [];
        $list = $this->service->parse_result($result);

        $columns = [
            'update_id' => 'ID',
            'from'      => 'From',
            'type'      => 'Type',
            'date'      => 'Date',
            'text'      => 'Text'
        ];
        return view('content_list', [
            'active'    => 'tb',
            'title'     => 'Run Bot - '.$name,
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'   => $columns,
            'list'      => $this->parse_list($list, $columns)
        ]);
    }

    /**
     * cURL TelegramBot API getUpdates
     * @param string $name Bot username
     * @return string
     */
    public function read($name) {
        $this->breadcrumbs = ['Telegram Bots'=>'/tb',$name.' Reading'=>'/tb/read/'.$name];
        $result = $this->service->readGetUpdates($name);
        $columns = ['update_id'=>'ID','from'=>'From','type'=>'Type','date'=>'Date','text'=>'Text'];
        return view('content_list', [
            'active'    => 'tb_bots',
            'title'     => 'Bot - '.$name,
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'   => $columns,
            'list'      => $this->parse_list($result, $columns)
        ]);
    }

    /**
     * Get chat List from Mysql
     * @return string
     */
    public function chats() {
        $this->breadcrumbs = ['Telegram Chats'=>'/tb/chats'];
        $result = $this->service->chats();
        $columns = ['id'=>'ID','title'=>'Title','type'=>'Type','operations'=>'Operations'];
        $btns = ['tg_chat_messages' => 'id'];
        return view('content_list', [
            'active'    => 'tb_chats',
            'title'     => 'Telegram Chat List',
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'   => $columns,
            'list'      => $this->parse_list($result, $columns, $btns)
        ]);
    }

    /**
     * Get chat messages List from Mysql
     * @return string
     */
    public function chat_messages($id) {
        $this->breadcrumbs = ['Telegram Chats'=>'/tb/chats','Messages'=>'/tb/chat_messages/'.$id];
        $result = $this->service->chat_messages($id);
        $columns = [
            'id'        => 'ID',
            'date'      => 'Date',
            'from'      => 'From',
            'bot'       => 'Is Bot',
            'text'      => 'Text',
        ];
        return view('content_list', [
            'active'        => 'tb_chats',
            'title'         => 'Telegram Chat Messages',
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns)
        ]);
    }

    /**
     * Get user List from Mysql
     * @return string
     */
    public function users() {
        $this->breadcrumbs = ['Telegram Users'=>'/tb/users'];
        $result = $this->service->users();
        $columns = ['id'=>'ID','bot'=>'Is Bot','first_name'=>'First Name','last_name'=>'Last Name','username'=>'username','operations'=>'Operations'];
        $btns = ['tg_user_messages' => 'id'];
        return view('content_list', [
            'active'        => 'tb_users',
            'title'         => 'Telegram User List',
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns, $btns)
        ]);
    }

    /**
     * Get user List from Mysql
     * @return string
     */
    public function user_messages($id) {
        $this->breadcrumbs = ['Telegram Users'=>'/tb/users','Messages'=>'/tb/user_messages/'.$id];
        $result = $this->service->user_messages($id);
        $columns = ['id'=>'ID','chat_title'=>'Group','date'=>'Date','text'=>'Text'];
        return view('content_list', [
            'active'        => 'tb_users',
            'title'         => 'Telegram User Messages',
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns)
        ]);
    }
}
