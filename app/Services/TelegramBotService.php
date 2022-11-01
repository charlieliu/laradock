<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

/**
 * Class TelegramBotService
 */
class TelegramBotService
{
    protected $default_chat = [
        'id' => '',
        'title' => '',
        'type' => ''
    ];

    protected $default_member = [
        'id' => '',
        'is_bot' => false,
        'first_name' => '',
        'last_name' => '',
        'username' => '',
        'language_code' => ''
    ];

    public $messages = [];
    public $polls = [];
    public $chats = [];
    public $members = [];
    public $new_chat_members = [];
    public $left_chat_member = [];
    public $BotName = '';
    public $ApiKey = '';
    public $userID = 0;

    private $telegram;

    /**
     * @return array
     */
    public function bots() {
        $output = [];
        $sql = 'SELECT `telegram_bots`.*,';
        $sql .= ' COALESCE(`user`.`id`, 0) AS `user_id`,';
        $sql .= ' COALESCE(`user`.`first_name`, "") AS `first_name`,';
        $sql .= ' COALESCE(`user`.`last_name`, "") AS `last_name`';
        $sql .= ' FROM `default`.`telegram_bots`';
        $sql .= ' LEFT JOIN `default`.`user` ON `telegram_bots`.username = `user`.username';
        $response = (array) DB::select($sql);
        foreach ($response as $row) {
            $tmp = (array) $row;
            $output[$tmp['username']] = $tmp;
        }

        return $output;
    }

    /**
     * Select Mysql chat
     * @return array
     */
    public function chats() {
        $output = [];
        $userIDs = [];
        $response = (array) DB::select('SELECT * FROM `chat`');
        foreach ($response as $row) {
            $tmp = (array) $row;
            if (is_null($tmp['title'])) {
                $tmp['title'] =  '';
                $userIDs[] = $tmp['id'];
            }
            $output[$tmp['id']] = $tmp;
        }
        if ( ! empty($userIDs)) {
            $response = (array) DB::select('SELECT * FROM `user` WHERE id IN('.implode(',',$userIDs).')');
            foreach ($response as $row) {
                $tmp = (array) $row;
                $title = '';
                if ( ! empty($tmp['first_name']) ||  ! empty($tmp['last_name'])) {
                    $title = $tmp['first_name'].' '.$tmp['last_name'];
                }
                if ( ! empty($tmp['username'])) {
                    $title = $tmp['username'];
                }
                if ( ! empty($title)) {
                    $output[$tmp['id']]['title'] = $title;
                }
            }
        }
        return $output;
    }

    /**
     * Select Mysql chat
     * @return array
     */
    public function chatMessages($id) {
        $output = [];
        $sql = 'SELECT `message`.*,';
        $sql .= ' COALESCE(`user`.`id`, 0) AS `user_id`,';
        $sql .= ' COALESCE(`user`.`is_bot`, 0) AS `is_bot`,';
        $sql .= ' COALESCE(`user`.`first_name`, "") AS `first_name`,';
        $sql .= ' COALESCE(`user`.`last_name`, "") AS `last_name`,';
        $sql .= ' COALESCE(`user`.`username`, "") AS `username`';
        $sql .= ' FROM `default`.`message`';
        $sql .= ' LEFT JOIN `default`.`user` ON `user`.`id` = `message`.`user_id`';
        $sql .= ' WHERE `message`.`chat_id`='.(int) $id;
        $sql .= ' ORDER BY `message`.`id` ASC';
        $response = (array) DB::select($sql);
        foreach ($response as $row) {
            $tmp = (array) $row;
            $tmp['from'] = '';
            if ( ! empty($tmp['first_name']) ||  ! empty($tmp['last_name'])) {
                $tmp['from'] = $tmp['first_name'].' '.$tmp['last_name'];
            }
            if ( ! empty($tmp['username'])) {
                $tmp['from'] = $tmp['username'];
            }
            $tmp['text'] = is_null($tmp['text']) ? '' : $tmp['text'];
            $tmp['bot'] = $tmp['is_bot'] === 1 ? 'Yes' : 'No';
            $output[] = $tmp;
        }
        return $output;
    }

    /**
     * Select Mysql user
     * @return array
     */
    public function users() {
        $output = [];
        $response = (array) DB::select('SELECT * FROM `user`');
        foreach ($response as $row) {
            $tmp = $this->default_member;
            $row = (array) $row;
            foreach ($row as $key => $value) {
                if ( ! is_null($value) || is_bool($value)) {
                    $tmp[$key] = $value;
                }
            }
            $tmp['bot'] = $row['is_bot'] === 1 ? 'Yes' : 'No';
            $output[] = $tmp;
        }
        return $output;
    }

    /**
     * Select Mysql user messages
     * @return array
     */
    public function userMessages($id) {
        $output = [];
        $sql = 'SELECT `message`.*,';
        $sql .= ' COALESCE(`chat`.`type`, "") AS `chat_type`,';
        $sql .= ' COALESCE(`chat`.`title`, "") AS `chat_title`';
        $sql .= ' FROM `default`.`message`';
        $sql .= ' LEFT JOIN `default`.`chat` ON `chat`.`id` = `message`.`chat_id`';
        $sql .= ' WHERE `message`.`user_id`='.(int) $id;
        $sql .= ' ORDER BY `message`.`chat_id` ASC, `message`.`id` ASC';
        $response = (array) DB::select($sql);
        foreach ($response as $row) {
            $tmp = (array) $row;
            $tmp['text'] = is_null($tmp['text']) ? '' : $tmp['text'];
            $output[] = $tmp;
        }
        return $output;
    }

    public function logInfo($method = '', $info = '') {
        Log::debug(date('Y-m-d H:i:s') . ' ' .$method. ' '.$info);
    }

    /**
     * get Bot token
     * @param string $name Bot username
     * @return string $ApiKey Bot token
     */
    public function getToken($BotName) {
        $bots = $this->bots();
        if ( ! isset($bots[$BotName]) || ! isset($bots[$BotName]['api_key'])) {
            echo __METHOD__.' Bot ['.$BotName.'] not exist';
            exit;
        }
        $this->BotName = $BotName;
        $this->ApiKey = $bots[$BotName]['api_key'];
        $this->userID = is_null($bots[$BotName]['user_id']) ? 0 : $bots[$BotName]['user_id'];
        return $this->ApiKey;
    }

    /**
     * getUpdates and write to Mysql
     * @param string $name Bot username
     * @return string
     */
    public function runGetUpdates($BotName) {
        $time_start = microtime(true);
        $this->logInfo(__METHOD__, ' START');

        $ApiKey = $this->getToken($BotName);

        try {
            // Create Telegram API object
            $this->telegram = new \Longman\TelegramBot\Telegram($ApiKey, $BotName);

            // Enable MySQL
            $this->telegram->enableMySql([
                'host'     => env('DB_HOST'),
                'port'     => env('DB_PORT'),
                'user'     => env('DB_USERNAME'),
                'database' => env('DB_DATABASE'),
                'password' => env('DB_PASSWORD')
            ])->useGetUpdatesWithoutDatabase(false);
            $rs = $this->telegram->handleGetUpdates();
            if ( ! isset($rs->ok) || $rs->ok !== true) {
                return false;
            }

            $result = ! empty($rs->result) ? (array) $rs->result : [];
            $this->parseResult($result);

            $this->messagesEvent();
            $this->newChatMembersEvent();

            $time_end = microtime(true);
            $this->logInfo(__METHOD__, 'END / USED '.($time_end - $time_start) . ' s');
            return $rs;
        } catch (\Longman\TelegramBot\Exception\TelegramException $e) {
            $this->logInfo(__METHOD__, 'ERROR '. json_encode($e->getMessage()));
        }
    }

    public function sendMessage($data = []) {
        $time_start = microtime(true);
        $this->logInfo(__METHOD__, ' START');

        $rs = Request::sendMessage($data);

        $time_end = microtime(true);
        $this->logInfo(__METHOD__, 'END / USED '.($time_end - $time_start) . ' s');
        return $rs;
    }

    /**
     * cURL TelegramBot API getUpdates
     * @param array $name Bot's username
     * @return array
     */
    public function readGetUpdates($name) {
        $ApiKey = '';

        // Check Bot's username
        foreach ($this->bots() as $row) {
            if ($row['username'] === $name) {
                $ApiKey = $row['api_key'];
            }
        }
        if (empty($ApiKey)) {
            echo 'Bot ['.$name.'] not exist';
            exit;
        }

        // cURL TelegramBot API getUpdates
        $url= 'https://api.telegram.org/bot'.$ApiKey.'/getUpdates';
        $client = new \GuzzleHttp\Client();
        $request = $client->request('GET', $url);
        $statusCode = $request->getStatusCode();
        if ($statusCode != 200) {
            echo 'HTTP CODE : '.$statusCode;
            exit;
        }
        $content = $request->getBody();
        $response = json_decode($content, true);
        if (empty($response['result'])) {
            return [];
        }
        $this->cleanTmp();
        $list = [];
        foreach ($response['result'] as $row) {
            $list[] = $this->parse_data($row);
        }
        return $list;
    }

    public function cleanTmp() {
        $this->messages = [];
        $this->polls = [];
        $this->chats = [];
        $this->members = [];
        $this->new_chat_members = [];
        $this->left_chat_member = [];
    }

    /**
     * Parse Data for view
     * @param array $input each row data
     * @return array $output
     */
    public function parseResult($input) {
        $this->cleanTmp();
        $output = [];
        foreach ((array)$input as $row) {
            $tmp = $this->parseData((array)$row);
            $output[] = $tmp;
        }
        return $output;
    }

    /**
     * Parse Data for view
     * @param array $input each row data
     * @return array $output
     */
    public function parseData($input) {
        $output = [
            'update_id' => isset($input['update_id']) ? $input['update_id'] : 0,
            'chat' => '',
            'type' => '',
            'from' => '--',
            'date' => '----/--/--',
            'text' => ''
        ];

        // message
        if ( ! empty($input['message'])) {
            $output['type'] = 'message';
            $message = $this->parseMessage($input);
            $output['text'] = $message['text'];
            if ( ! empty($message['date'])) {
                $output['date'] = date('Y/m/d H:i:s', $message['date']);
            }
            if ( ! empty($message['from'])) {
                $output['from'] = $message['from'];
            }
            if ( ! empty($message['chat_name'])) {
                $output['chat'] = $message['chat_name'];
            }
            echo __METHOD__.' output : ' . json_encode($output) . PHP_EOL;
            return $output;
        }

        if ( ! empty($input['edited_message'])) {
            $output['type'] = 'edited_message';
            $message = $this->parseMessage($input, 'edited_message');
            $output['text'] = $message['text'];
            if ( ! empty($message['date'])) {
                $output['date'] = date('Y/m/d H:i:s', $message['date']);
            }
            if ( ! empty($message['from'])) {
                $output['from'] = $message['from'];
            }
            echo __METHOD__.' output : ' . json_encode($output) . PHP_EOL;
            return $output;
        }

        // poll
        if ( ! empty($input['poll'])) {
            $output['type'] = 'poll';
            $poll = $input['poll'];
            $this->polls[$poll['id']] = $poll;
            $output['text'] = $poll['question'].' : '.json_encode($poll['options']);
            if ( ! empty($poll['from'])) {
                $output['from'] = $poll['from'];
            }
            echo __METHOD__.' output : ' . json_encode($output) . PHP_EOL;
            return $output;
        }

        // channel
        if ( ! empty($input['channel_post'])) {
            $output['type'] = 'channel_post';
            $message = $this->parseMessage($input, 'channel_post');
            if ( ! empty($message['from'])) {
                $output['from'] = $message['from'];
            }
            echo __METHOD__.' output : ' . json_encode($output) . PHP_EOL;
            return $output;
        }

        // chat member
        if ( ! empty($input['my_chat_member'])) {
            $output['type'] = 'my_chat_member';
            $message = $this->parseMyChatMember($input);
            if ( ! empty($message['from'])) {
                $output['from'] = $message['from'];
            }
            echo __METHOD__.' output : ' . json_encode($output) . PHP_EOL;
            return $output;
        }
        echo __METHOD__.' LINE : '.__LINE__.' '.json_encode($input);exit;
    }

    /**
     * @param array $input
     * @param string $item parse item name
     * @param array $default default output
     * @return array $default
     */
    public function parseItem($input, $item, $default) {
        foreach ($default as $key => $value) {
            if (isset($input[$item][$key])) {
                $default[$key] = $input[$item][$key];
            }
        }
        return $default;
    }

    /**
     * @param array $input
     * @param string $item parse item name
     * @return array $output
     */
    public function parseChat($input, $item = 'chat') {
        $output = $this->parseItem($input, $item, $this->default_chat);
        if ( ! empty($output['id'])) {
            $this->chats[$output['id']] = $output;
        }
        return $output;
    }

    /**
     * @param array $input
     * @param string $item parse item name
     * @return array $output
     */
    public function parseMember($input, $item = 'from') {
        $output = $this->parseItem($input, $item, $this->default_member);
        if ( ! empty($output['id'])) {
            $this->members[$output['id']] = $output;
        }
        return $output;
    }

    /**
     * @param array $input
     * @param int $chat_id
     * @return array
     */
    public function parseChatMember($input, $chat_id = 0) {
        $output = $this->default_member;
        $output['chat_id'] = $chat_id;
        $output['name'] = '';
        foreach (array_keys($this->default_member) as $key) {
            if (isset($input[$key])) {
                $output[$key] = $input[$key];
            }
        }
        if ( ! empty($output['first_name'])) {
            $output['name'] = $output['first_name'];
        }
        if (empty($output['name']) && ! empty($output['username'])) {
            $output['name'] = '@'.$output['username'];
        }
        return $output;
    }

    /**
     * @param array $input
     * @param string $item parse item name
     * @return array
     */
    public function parseMessage($input, $type = 'message') {
        if (empty($input[$type])) {
            echo __METHOD__.' LINE : '.__LINE__.' '.json_encode($input);exit;
        }
        $member = $this->parseMember($input[$type]);
        $chat = $this->parseChat($input[$type], 'chat');
        $output = [
            'id' => $input[$type]['message_id'],
            'chat_id' => $chat['id'],
            'chat_name' => '',
            'member_id' => $member['id'],
            'from' => '--',
            'date' => '----/--/--',
            'text' => ''
        ];
        if (isset($this->chats[$chat['id']]['title'])) {
            $output['chat_name'] = $this->chats[$chat['id']]['title'];
        }
        if ( ! empty($member['first_name']) || ! empty($member['last_name'])) {
            $output['from'] = $member['first_name'].' '.$member['last_name'];
        }
        if ( ! empty($member['username'])) {
            $output['from'] = '@'.$member['username'];
        }
        if ( ! empty($input[$type]['date'])) {
            $output['date'] = $input[$type]['date'];
        }
        if (isset($input[$type]['text'])) {
            $output['text'] = $input[$type]['text'];
        }
        if ( ! empty($input[$type]['sender_chat'])) {
            $this->parse_chat($input[$type], 'sender_chat');
        }
        if ( ! empty($input[$type]['old_chat_member'])) {
            $chat_member = $this->parseChatMember($input[$type]['old_chat_member'], $chat['id']);
        }
        if ( ! empty($input[$type]['new_chat_member'])) {
            $chat_member = $this->parseChatMember($input[$type]['new_chat_member'], $chat['id']);
            if ( ! empty($chat_member['id'])) {
                $this->new_chat_members[$chat['id']][$chat_member['id']] = $chat_member;
            }
        }
        if ( ! empty($input[$type]['new_chat_members'])) {
            foreach ($input[$type]['new_chat_members'] as $new_chat_member) {
                $chat_member = $this->parseChatMember($new_chat_member, $chat['id']);
                if ( ! empty($chat_member['id'])) {
                    $this->new_chat_members[$chat['id']][$chat_member['id']] = $chat_member;
                }
            }
            $output['text'] .= ' Add '.$new_chat_member['first_name'];
        }
        if ( ! empty($input[$type]['left_chat_member'])) {
            $left_chat_member = $input[$type]['left_chat_member'];
            $this->left_chat_members[$left_chat_member['id']] = $left_chat_member;
            $output['text'] .= ' Remove '.$left_chat_member['first_name'];
        }
        if ( ! empty($input[$type]['new_chat_photo'])) {
            foreach ($input[$type]['new_chat_photo'] as $photo) {
                // Todo
            }
        }
        $this->messages[$output['id']] = $output;
        if ( ! empty($input[$type]['reply_to_message'])) {
            $reply_to_message = $this->parseMessage($input[$type], 'reply_to_message');
            $output['text'] = $reply_to_message['text'].' => '.$output['text'];
        }
        return $output;
    }

    /**
     * @param array $input
     * @param string $type parse item name
     * @return array
     */
    public function parseMyChatMember($input, $type = 'my_chat_member') {
        $member = $this->parseMember($input[$type]);
        $chat = $this->parseChat($input[$type], 'chat');
        $output = [
            'id' => '',
            'chat_id' => $chat['id'],
            'member_id' => $member['id'],
            'from' => '--',
            'date' => '----/--/--',
            'text' => ''
        ];
        if ( ! empty($member['first_name']) || ! empty($member['last_name'])) {
            $output['from'] = $member['first_name'].' '.$member['last_name'];
        }
        if ( ! empty($member['username'])) {
            $output['from'] = '@'.$member['username'];
        }
        if ( ! empty($input[$type]['date'])) {
            $output['date'] = $input[$type]['date'];
        }
        if (isset($input[$type]['text'])) {
            $output['text'] = $input[$type]['text'];
        }
        if ( ! empty($input[$type]['sender_chat'])) {
            $this->parseChat($input[$type], 'sender_chat');
        }
        if ( ! empty($input[$type]['old_chat_member'])) {
            $chat_member = $this->parseChatMember($input[$type]['old_chat_member'], $chat['id']);
        }
        if ( ! empty($input[$type]['new_chat_member'])) {
            $chat_member = $this->parseChatMember($input[$type]['new_chat_member'], $chat['id']);
            if ( ! empty($chat_member['id'])) {
                $this->new_chat_members[$chat['id']][$chat_member['id']] = $chat_member;
            }
        }
        if ( ! empty($input[$type]['new_chat_members'])) {
            foreach ($input[$type]['new_chat_members'] as $new_chat_member) {
                $chat_member = $this->parseChatMember($new_chat_member, $chat['id']);
                if ( ! empty($chat_member['id'])) {
                    $this->new_chat_members[$chat['id']][$chat_member['id']] = $chat_member;
                }
            }
            $output['text'] .= ' Add new_chat_member';
        }
        if ( ! empty($input[$type]['left_chat_member'])) {
            $chat_member = $input[$type]['left_chat_member'];
            $this->left_chat_members[$chat_member['id']] = $chat_member;
            $output['text'] .= ' Remove '.$chat_member['first_name'];
        }
        if ( ! empty($input[$type]['new_chat_photo'])) {
            foreach ($input[$type]['new_chat_photo'] as $photo) {
                // $output['text'] .= $photo['file_id'];
            }
        }
        if ( ! empty($input[$type]['reply_to_message'])) {
            $reply_to_message = $this->parseMessage($input[$type], 'reply_to_message');
            $output['text'] = $reply_to_message['text'].' => '.$output['text'];
        }
        return $output;
    }

    /**
     * messages event
     */
    public function messagesEvent() {
        foreach ($this->messages as $message) {
            // echo 'message : ' . json_encode($message) . PHP_EOL;
            if ($this->userID == $message['member_id']) {
                continue; // message from bot self
            }
            if ($message['chat_id'] == $message['member_id']) {
                // message from bot self
                continue;
            }
            if ( ! empty($message['text']) &&  ! empty($message['chat_id'])) {
                if (strpos(strtolower($message['text']) , 'hi') !== false ) {
                    $sendResult = $this->sendMessage([
                        'chat_id' => $message['chat_id'],
                        'text' => 'Hi I am bot.'
                    ]);
                    echo '$sendResult : ' . json_encode($sendResult) . PHP_EOL;
                    if ($sendResult->isOk()) {
                        $this->logInfo(__METHOD__, 'Message sent succesfully to: ' . $message['chat_id']);
                    } else {
                        $this->logInfo(__METHOD__, 'Sorry message not sent to: ' . $message['chat_id']);
                    }
                }
            }
        }
    }

    /**
     * new chat members event
     */
    public function newChatMembersEvent() {
        foreach ($this->new_chat_members as $chat_id => $chat_members) {
            foreach ($chat_members as $member) {
                echo __METHOD__.' $member : ' . json_encode($member) . PHP_EOL;
                if (empty($chat_id) || empty($member['id'])) {
                    continue;
                }

                // set permissions
                $this->restrictChatMember($member, true);

                $data = [
                    'chat_id' => $chat_id,
                    'text' => '['.$member['name'].'](tg://user?id='.$member['id'].') 歡迎加入本群組，請點擊下方按鈕以獲的發言權限',
                    'parse_mode' => 'MarkdownV2',
                    'reply_markup' => json_encode([
                        'keyboard' => [[['text'=>'👉🏻解禁我👈🏻']]],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true,
                        'input_field_placeholder' => '👉🏻解禁我👈🏻',
                        'selective' => true,
                    ])
                ];
                $sendResult = $this->sendMessage($data);
                if ($sendResult->isOk()) {
                    $this->logInfo(__METHOD__, 'Message sent succesfully to: ' . $chat_id);
                } else {
                    $this->logInfo(__METHOD__, 'Sorry message not sent to: ' . $chat_id);
                }

            }
        }
    }

    /**
     * set chat members permissions
     */
    public function restrictChatMember($member, $enabled = false) {
        if (empty($member['id']) || empty($member['chat_id'])) {
            return false;
        }
        $data = [
            'chat_id' => $member['chat_id'],
            'user_id' => $member['id'],
            'permissions' => json_encode([
                'can_send_messages' => $enabled,
                'can_send_media_messages' => false,
                'can_send_polls' => $enabled,
                'can_send_other_messages' => false,
                'can_add_web_page_previews' => false,
                'can_change_info' => false,
                'can_invite_users' => false,
                'can_pin_messages' => false
            ])
        ];
        $sendResult = Request::send('restrictChatMember', $data);
        if ($sendResult->isOk()) {
            $this->logInfo(__METHOD__, 'Message sent succesfully to: ' . $member['chat_id']);
        } else {
            $this->logInfo(__METHOD__, 'Sorry message not sent to: ' . $member['chat_id']);
        }
    }
}
