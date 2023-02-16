<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use App\Services\TelegramBotService;

class TelegramBotController extends Controller
{
    public $breadcrumbs = [];
    private $service;

    public function __construct(TelegramBotService $telegramBotService) {
        $this->service = $telegramBotService;
    }

    /**
     * Telegram Bot list from Mysql
     * @return string
     */
    public function bots() {
        $this->breadcrumbs = ['Telegram Bots'=>'/tg_bot'];
        $columns = [
            'id'            => 'ID',
            'username'      => 'Bot Name',
            'user_id'       => 'User ID',
            'first_name'    => 'First Name',
            'last_name'     => 'Last Name',
            'operations'    => 'Operations'
        ];
        $result = $this->service->bots();
        $buttons = [
            'tg_detail' => 'username',
            'tg_run'    => 'username',
            'tg_link'   => 'username',
        ];
        return view('content_list', [
            'active'        => 'tg_bots',
            'title'         => 'Telegram Bot List',
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns, $buttons)
        ]);
    }

    /**
     * getUpdates and write to Mysql
     * @param string $name Bot username
     * @return string
     */
    public function run($name) {
        $this->breadcrumbs = ['Telegram Bots'=>'/tg_bot',$name.' Run'=>'/tg_bot/run/'.$name];
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
        $list = $this->service->parseResult($result);

        $columns = [
            'update_id' => 'ID',
            'from'      => 'From',
            'type'      => 'Type',
            'date'      => 'Date',
            'text'      => 'Text'
        ];
        return view('content_list', [
            'active'        => 'tg_bot',
            'title'         => 'Run Bot - '.$name,
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'       => $columns,
            'list'          => $this->parse_list($list, $columns)
        ]);
    }

    /**
     * cURL TelegramBot API getUpdates
     * @param string $name Bot username
     * @return string
     */
    public function read($name) {
        $this->breadcrumbs = ['Telegram Bots'=>'/tg_bot',$name.' Reading'=>'/tg_bot/read/'.$name];
        $result = $this->service->readGetUpdates($name);
        $columns = ['update_id'=>'ID','from'=>'From','type'=>'Type','date'=>'Date','text'=>'Text'];
        return view('content_list', [
            'active'        => 'tg_bots',
            'title'         => 'Bot - '.$name,
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns)
        ]);
    }

    /**
     * Get chat List from Mysql
     * @return string
     */
    public function chats() {
        $this->breadcrumbs = ['Telegram Chats'=>'/tg_bot/chats'];
        $result = $this->service->chats();
        $columns = ['id'=>'ID','title'=>'Title','type'=>'Type','operations'=>'Operations'];
        $buttons = ['tg_chat_messages' => 'id'];
        return view('content_list', [
            'active'        => 'tg_chats',
            'title'         => 'Telegram Chat List',
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns, $buttons)
        ]);
    }

    /**
     * Get chat messages List from Mysql
     * @return string
     */
    public function chatMessages($id) {
        $model = new Chat();
        $chat = $model::getOne($id);
        $this->service->logInfo(__METHOD__, var_export($chat, true));
        $result = [];
        $tab = 'Messages';
        if ( ! empty($chat)) {
            $result = $this->service->chatMessages($id);
            $tab = '';
            if ($chat->type == 'private') {
                if ( ! empty($chat->first_name)) {
                    $tab .= $chat->first_name;
                }
                if ( ! empty($chat->last_name)) {
                    $tab .= $chat->last_name;
                }
            } else {
                $tab .= $chat->title;
            }
            $tab .= ' Messages';
        }
        $this->breadcrumbs = ['Telegram Chats'=>'/tg_bot/chats',$tab=>'/tg_bot/chat_messages/'.$id];
        $columns = [
            'id'        => 'ID',
            'date'      => 'Date',
            'from'      => 'From',
            'bot'       => 'Is Bot',
            'text'      => 'Text',
        ];
        return view('content_list', [
            'active'        => 'tg_chats',
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
        $this->breadcrumbs = ['Telegram Users'=>'/tg_bot/users'];
        $result = $this->service->users();
        $columns = ['id'=>'ID','bot'=>'Is Bot','first_name'=>'First Name','last_name'=>'Last Name','username'=>'username','operations'=>'Operations'];
        $buttons = ['tg_user_messages' => 'id'];
        return view('content_list', [
            'active'        => 'tg_users',
            'title'         => 'Telegram User List',
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns, $buttons)
        ]);
    }

    /**
     * Get user List from Mysql
     * @return string
     */
    public function userMessages($id) {
        $model = new User();
        $user = $model::getOne($id);
        $this->service->logInfo(__METHOD__, var_export($user, true));
        $result = [];
        $tab = 'Messages';
        if ( ! empty($user)) {
            $result = $this->service->userMessages($id);
            $tab = '';
            if ( ! empty($user->first_name)) {
                $tab .= $user->first_name;
            }
            if ( ! empty($user->last_name)) {
                $tab .= $user->last_name;
            }
            $tab .= ' Messages';
        }
        $this->breadcrumbs = ['Telegram Users'=>'/tg_bot/users',$tab=>'/tg_bot/user_messages/'.$id];
        $columns = [
            'id'        => 'ID',
            'chat_title'=> 'Group',
            'chat_type' => 'Group Type',
            'date'      => 'Date',
            'text'      => 'Text'
        ];
        return view('content_list', [
            'active'        => 'tg_users',
            'title'         => 'Telegram User Messages',
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns)
        ]);
    }
}
