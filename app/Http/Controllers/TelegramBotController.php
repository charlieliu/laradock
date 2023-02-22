<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use App\Models\TelegramBots;
use App\Models\Message;
use App\Models\MessageHistory;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TelegramBotController extends Controller
{
    private $service;

    public function __construct(TelegramBotService $telegramBotService) {
        $this->service = $telegramBotService;
    }

    /**
     * Telegram Bot list from Mysql
     * @return string
     */
    public function bots() {
        $columns = [
            'id'            => 'ID',
            'username'      => 'Bot Name',
            'user_id'       => 'User ID',
            'first_name'    => 'First Name',
            'last_name'     => 'Last Name',
            'last_update_id'=> 'Last Update ID',
            'start_at'      => 'Start at',
            'done'          => 'Done',
            'operations'    => 'Operations'
        ];
        Cache::forget('bots');
        $result = $this->service->bots();
        foreach ($result as $key => $bot) {
            $worker = Cache::get('worker:'.$bot['username']);
            $worker = empty($worker) ? [] : $worker;
            $worker = is_array($worker) ? $worker : json_decode($worker, true);
            $result[$key]['start_at'] = isset($worker['start_at']) ? $worker['start_at'] : '--';
            $result[$key]['done'] = isset($worker['done']) ? $worker['done'] : '--';
        }
        $buttons = [
            'tg_bot_edit'   => 'id',
            'tg_detail'     => 'username',
            'tg_sync'       => 'username',
            'tg_link'       => 'username',
        ];
        return view('content_list', [
            'active'        => 'tg_bots',
            'title'         => 'Telegram Bot List',
            'breadcrumbs'   => ['Telegram Bots'=>'/tg_bot'],
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns, $buttons)
        ]);
    }

    /**
     * Telegram Bot Detail
     * @param int $id Bot ID
     * @return string
     */
    public function bot($id=0) {
        $bot = [];
        if ( ! empty($id) && is_numeric($id)) {
            $model = new TelegramBots();
            $bot = (array) $model::getOneById($id);
            if (empty($bot['user_id']) && ! empty($bot['api_key'])) {
                $explode = explode(':', $bot['api_key']);
                $bot['user_id'] = (int) $explode[0];
            }
        }
        $columns = [
            'id'            => ['name'=>'ID','value'=>'','disabled'=>true],
            'username'      => ['name'=>'Bot User Name','value'=>'','disabled'=>true],
            'user_id'       => ['name'=>'Bot User ID','value'=>'','disabled'=>true],
            'first_name'    => ['name'=>'First Name','value'=>'','disabled'=>true],
            'last_name'     => ['name'=>'Last Name','value'=>'','disabled'=>true],
            'api_key'       => ['name'=>'Bot token','value'=>'','disabled'=>false],
        ];
        $title = 'Create Bot';
        if ( ! empty($bot)) {
            $title = 'Edit Bot '.$bot['username'];
            foreach ($bot as $key => $value) {
                if ( isset($columns[$key])) {
                    $columns[$key]['value'] = $value;
                }
            }
        }
        return view('content_edit', [
            'active'        => 'tg_bots_edit',
            'form_action'   => '/tg_bot/bot/'.$id,
            'title'         => $title,
            'breadcrumbs'   => [
                'Telegram Bots' => '/tg_bot',
                $title          => '/tg_bot/bot/'.$id
            ],
            'detail'        => $columns
        ]);
    }

    /**
     * Telegram Bot getMe By token and Update bot information
     * @param int $id Bot ID
     * @return string
     */
    public function botEdit($bot_id=0, Request $request) {
        $userModel = new User();
        $botModel = new TelegramBots();
        $token = $request->post('api_key');
        $response = $this->service->getMe($token);
        $title = ( ! empty($bot_id) && is_numeric($bot_id)) ? 'Edit Bot' : 'Create Bot';
        $breadcrumbs = [
            'Telegram Bots' => '/tg_bot',
            $title          => '/tg_bot/bot/'.$bot_id
        ];
        if ($response['ok'] !== true
            || empty($response['result'])
            || empty($response['result']['id'])
            || ! isset($response['result']['username'])
        ) {
            $breadcrumbs['Error'] = '/tg_bot';
            return view('content_p', [
                'active'        => 'tg_bots_edit',
                'title'         => $title,
                'breadcrumbs'   => $breadcrumbs,
                'content'       => 'Get bot information Error '.json_encode($response)
            ]);
        }
        $user_id = (int) $response['result']['id'];
        $user = [];
        foreach (['is_bot','first_name','last_name','username'] as $column) {
            if (isset($response['result'][$column])) {
                $user[$column] = $response['result'][$column];
            }
        }
        $user['is_bot'] = $user['is_bot'] === true ? 1 : 0;
        $user['updated_at'] = date('Y-m-d H:i:s');
        $exist = $userModel::getOne($user_id);
        if (empty($exist)) {
            $user['id'] = $user_id;
            $user['created_at'] = $user['updated_at'];
            $result = $userModel::insertData($user);
            if (empty($result)) {
                $breadcrumbs['Error'] = '/tg_bot';
                return view('content_p', [
                    'active'        => 'tg_bots_edit',
                    'title'         => $title,
                    'breadcrumbs'   => $breadcrumbs,
                    'content'       => 'Create user('.$user_id.') Error '. json_encode($user)
                ]);
            }
        } else {
            $result = $userModel::updateData($user_id, $user);
            if (empty($result)) {
                $breadcrumbs['Error'] = '/tg_bot';
                return view('content_p', [
                    'active'        => 'tg_bots_edit',
                    'title'         => $title,
                    'breadcrumbs'   => $breadcrumbs,
                    'content'       => 'Update user('.$user_id.') Error '. json_encode($user)
                ]);
            }
        }
        $exist = (array) $botModel::getOneByUsername($user['username']);
        $bot = [
            'user_id'   => $user_id,
            'username'  => $user['username'],
            'api_key'   => $token,
            'updated_at'=> date('Y-m-d H:i:s')
        ];
        if (empty($bot_id)) {
            if (empty($exist)) {
                // Create a new bot
                $bot['created_at'] = $bot['updated_at'];
                $bot_id = $botModel::insertData($bot);
                if (empty($bot_id)) {
                    $breadcrumbs['Error'] = '/tg_bot';
                    return view('content_p', [
                        'active'        => 'tg_bots_edit',
                        'title'         => 'Create Bot '.$user['username'],
                        'breadcrumbs'   => $breadcrumbs,
                        'content'       => 'Create bot Error '. json_encode($bot)
                    ]);
                }
                Cache::forget('bots');
                $breadcrumbs = [
                    'Telegram Bots' => '/tg_bot',
                    $title          => '/tg_bot/bot/'.$bot_id,
                    'Success'       => '/tg_bot/bot/'.$bot_id,
                ];
                return view('content_p', [
                    'active'        => 'tg_bots_edit',
                    'title'         => 'Create Bot '.$user['username'],
                    'breadcrumbs'   => $breadcrumbs,
                    'content'       => 'Create bot('.$bot_id.') username:'.$user['username']
                ]);
            }

            // Create a new bot but it is exists. Update bot information
            $bot_id = (int) $exist['id'];
            $result = $botModel::updateData($exist['id'], $bot);
            if (empty($result)) {
                $breadcrumbs = [
                    'Telegram Bots' => '/tg_bot',
                    $title          => '/tg_bot/bot/'.$bot_id,
                    'Error'         => '/tg_bot',
                ];
                return view('content_p', [
                    'active'        => 'tg_bots_edit',
                    'title'         => 'Update Bot '.$user['username'],
                    'breadcrumbs'   => $breadcrumbs,
                    'content'       => 'Update bot Error '. json_encode($bot)
                ]);
            }
            Cache::forget('bots');
            Cache::forget('bot:'.$bot_id);
            $breadcrumbs['Success'] = '/tg_bot';
            return view('content_p', [
                'active'        => 'tg_bots_edit',
                'title'         => 'Update Bot '.$user['username'],
                'breadcrumbs'   => $breadcrumbs,
                'content'       => 'Update bot('.$bot_id.') username:'.$user['username']
            ]);
        }
        if ($exist['id'] == $bot_id) {
            // Update bot information
            $bot_id = (int) $exist['id'];
            $result = $botModel::updateData($exist['id'], $bot);
            if (empty($result)) {
                $breadcrumbs = [
                    'Telegram Bots' => '/tg_bot',
                    $title          => '/tg_bot/bot/'.$bot_id,
                    'Error'         => '/tg_bot',
                ];
                return view('content_p', [
                    'active'        => 'tg_bots_edit',
                    'title'         => $title,
                    'breadcrumbs'   => $breadcrumbs,
                    'content'       => 'Update bot Error '. json_encode($bot)
                ]);
            }
            Cache::forget('bots');
            Cache::forget('bot:'.$bot_id);
            $breadcrumbs = [
                'Telegram Bots' => '/tg_bot',
                $title          => '/tg_bot/bot/'.$bot_id,
                'Success'       => '/tg_bot/bot/'.$bot_id,
            ];
            return view('content_p', [
                'active'        => 'tg_bots_edit',
                'title'         => 'Update Bot '.$user['username'],
                'breadcrumbs'   => $breadcrumbs,
                'content'       => 'Update bot('.$bot_id.') username:'.$user['username']
            ]);
        }
        $breadcrumbs = [
            'Telegram Bots' => '/tg_bot',
            $title          => '/tg_bot/bot/'.$bot_id,
            'Error'         => '/tg_bot',
        ];
        return view('content_p', [
            'active'        => 'tg_bots_edit',
            'title'         => 'Update Bot '.$user['username'],
            'breadcrumbs'   => $breadcrumbs,
            'content'       => 'bot('.$exist['id'].') username Exists'
        ]);
    }

    /**
     * cURL TelegramBot API getUpdates
     * @param string $name Bot username
     * @return string
     */
    public function sync($name) {
        $result = $this->service->syncGetUpdates($name);
        $columns = ['update_id'=>'ID','from'=>'From','type'=>'Type','date'=>'Date','text'=>'Text'];
        return view('content_list', [
            'active'        => 'tg_bots',
            'title'         => 'Bot - '.$name,
            'breadcrumbs'   => [
                'Telegram Bots' => '/tg_bot',
                $name.' Reading'=> '/tg_bot/sync/'.$name],
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns)
        ]);
    }

    /**
     * Get chat List from Mysql
     * @return string
     */
    public function chats() {
        $result = $this->service->chats();
        $columns = ['id'=>'ID','title'=>'Title','type'=>'Type','operations'=>'Operations'];
        $buttons = ['tg_chat_messages' => 'id'];
        return view('content_list', [
            'active'        => 'tg_chats',
            'title'         => 'Telegram Chat List',
            'breadcrumbs'   => ['Telegram Chats'=>'/tg_bot/chats'],
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
            'breadcrumbs'   => [
                'Telegram Chats'=> '/tg_bot/chats',
                $tab            => '/tg_bot/chat_messages/'.$id
            ],
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns)
        ]);
    }

        /**
     * Get chat messages List from Mysql
     * @return string
     */
    public function chatMessageHistory($message_id) {
        $messageModel = new Message();
        $title = 'Telegram Chat Messages History';
        $breadcrumbs = [
            'Telegram Chats'    => '/tg_bot/chats',
            'Messages'          => '/tg_bot/chats',
            'History'           => '/tg_bot/chats'
        ];
        $message = (array) $messageModel::getOne($message_id);
        if (empty($message)) {
            return view('content_p', [
                'active'        => 'tg_chats',
                'title'         => $title.' Error',
                'breadcrumbs'   => $breadcrumbs,
                'content'       => 'Can not find message'
            ]);
        }
        $chatModel = new Chat();
        $chat = $chatModel::getOne($message['chat_id']);
        if (empty($chat)) {
            return view('content_p', [
                'active'        => 'tg_chats',
                'title'         => $title.' Error',
                'breadcrumbs'   => $breadcrumbs,
                'content'       => 'Can not find chat'
            ]);
        }
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
        $breadcrumbs = [
            'Telegram Chats'    => '/tg_bot/chats',
            $tab                => '/tg_bot/chat_messages/'.$chat->id,
            'History'           => '/tg_bot/chats'
        ];
        $historyModel = new MessageHistory();
        $result = $historyModel::getListAll($message_id);
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
            'breadcrumbs'   => [
                'Telegram Chats'    => '/tg_bot/chats',
                $tab                => '/tg_bot/chat_messages/'.$chat->id,
                'History'           => '/tg_bot/chat_messages/'.$message_id,
            ],
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns)
        ]);
    }

    /**
     * Get user List from Mysql
     * @return string
     */
    public function users() {
        $result = $this->service->users();
        $columns = ['id'=>'ID','bot'=>'Is Bot','first_name'=>'First Name','last_name'=>'Last Name','username'=>'username','operations'=>'Operations'];
        $buttons = ['tg_user_messages' => 'id'];
        return view('content_list', [
            'active'        => 'tg_users',
            'title'         => 'Telegram User List',
            'breadcrumbs'   => ['Telegram Users'=>'/tg_bot/users'],
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
            'breadcrumbs'   => ['Telegram Users'=>'/tg_bot/users',$tab=>'/tg_bot/user_messages/'.$id],
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns)
        ]);
    }
}
