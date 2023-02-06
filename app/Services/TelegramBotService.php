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
    /**
     * Constant for type from https://github.com/Eleirbag89/TelegramBotPHP/blob/master/Telegram.php
     */
    const INLINE_QUERY = 'inline_query';
    const CALLBACK_QUERY = 'callback_query';
    const EDITED_MESSAGE = 'edited_message';
    const REPLY = 'reply';
    const MESSAGE = 'message';
    const PHOTO = 'photo';
    const VIDEO = 'video';
    const AUDIO = 'audio';
    const VOICE = 'voice';
    const ANIMATION = 'animation';
    const STICKER = 'sticker';
    const DOCUMENT = 'document';
    const LOCATION = 'location';
    const CONTACT = 'contact';
    const CHANNEL_POST = 'channel_post';
    const NEW_CHAT_MEMBER = 'new_chat_member';
    const LEFT_CHAT_MEMBER = 'left_chat_member';

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
    public $callback_queries = [];
    public $BotName = '';
    public $ApiKey = '';
    public $userID = 0;
    public $input_data = [];
    public $output_data = [];

    private $telegram;

    public function logInfo($method = '', $info = '', $echo = false) {
        $log = date('Y-m-d H:i:s') . ' ' .$method. ' '.$info;
        Log::debug($log);
        if ( ! empty($echo)) {
            echo $log . PHP_EOL ;
        }
    }

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

    /**
     * get Bot token
     * @param string $name Bot username
     * @return string $ApiKey Bot token
     */
    public function getToken($BotName) {
        $bots = $this->bots();
        if ( ! isset($bots[$BotName]) || ! isset($bots[$BotName]['api_key'])) {
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' Bot ['.$BotName.'] not exist');
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
     * @param int $hold poll timeout
     * @return string|bool
     */
    public function runGetUpdates($BotName, $hold = 1) {
        $time_start = microtime(true);
        $this->logInfo(__METHOD__, 'START');

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

            // Get Telegram data set timeout $hold
            $rs = $this->telegram->handleGetUpdates(null, $hold);
            if ( ! isset($rs->ok) || $rs->ok !== true) {
                return false;
            }

            $time_end = microtime(true);
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' END');
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' USED '.($time_end - $time_start) . ' s');
            return $rs;
        } catch (\Longman\TelegramBot\Exception\TelegramException $e) {
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR '. json_encode($e->getMessage()), true);
        }
    }

    /**
     * send chat members message
     * @param array $input
     * @return object $rs
     */
    public function sendMessage($input = []) {
        $time_start = microtime(true);
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' START ' . json_encode($input));

        $rs = Request::sendMessage($input);

        $time_end = microtime(true);
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' END / USED '.($time_end - $time_start) . ' s');
        return $rs;
    }

    /**
     * send chat members message
     * @param array $input
     * @return object $rs
     */
    public function editMessageText($input = []) {
        $time_start = microtime(true);
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' START ' . json_encode($input));

        $sendResult = Request::send('editMessageText', $input);
        if ($sendResult->isOk()) {
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' Edit message text to: ' . $input['chat_id']);
        } else {
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' Sorry message not deleted to: ' . $input['chat_id']);
        }

        $time_end = microtime(true);
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' END / USED '.($time_end - $time_start) . ' s');
        return $sendResult;
    }

    /**
     * send chat members message
     * @param array $input
     * @return object $rs
     */
    public function editMessageReplyMarkup($input = []) {
        $time_start = microtime(true);
        $this->logInfo(__METHOD__, 'START ' . json_encode($input));

        $sendResult = Request::send('editMessageReplyMarkup', $input);
        if ($sendResult->isOk()) {
            $this->logInfo(__METHOD__, 'Edit message ReplyMarkup to: ' . $input['chat_id']);
        } else {
            $this->logInfo(__METHOD__, 'Sorry message not deleted to: ' . $input['chat_id']);
        }

        $time_end = microtime(true);
        $this->logInfo(__METHOD__, 'END / USED '.($time_end - $time_start) . ' s');
        return $sendResult;
    }

    /**
     * send chat members message
     * @param array $input
     * @return object $rs
     */
    public function deleteMessage($input = []) {
        $time_start = microtime(true);
        $this->logInfo(__METHOD__, 'START ' . json_encode($input));

        $sendResult = Request::send('deleteMessage', $input);
        if ($sendResult->isOk()) {
            $this->logInfo(__METHOD__, 'Message deleted succesfully to: ' . $input['chat_id']);
        } else {
            $this->logInfo(__METHOD__, 'Sorry message not deleted to: ' . $input['chat_id']);
        }

        $time_end = microtime(true);
        $this->logInfo(__METHOD__, 'END / USED '.($time_end - $time_start) . ' s');
        return $sendResult;
    }

    /**
     * set chat members permissions
     * @param array $member user info
     * @param boolean $enabled enabled
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
            $this->logInfo(__METHOD__, 'Bot ['.$name.'] not exist');
            exit;
        }

        // cURL TelegramBot API getUpdates
        $url= 'https://api.telegram.org/bot'.$ApiKey.'/getUpdates';
        $client = new \GuzzleHttp\Client();
        $request = $client->request('GET', $url);
        $statusCode = $request->getStatusCode();
        if ($statusCode != 200) {
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' HTTP CODE : '.$statusCode);
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
            $list[] = $this->parseData($row);
        }
        return $list;
    }

    /**
     * Parse Data for view
     * @param array $input each row data
     * @return array $output
     */
    public function parseResult($input) {
        $this->logInfo(__METHOD__, 'input : '.json_encode($input));
        $this->cleanTmp();
        $output = [];
        foreach ((array)$input as $row) {
            $tmp = $this->parseData((array)$row);
            $output[] = $tmp;
        }
        $this->logInfo(__METHOD__, 'input : '.json_encode($output));
        return $output;
    }

    public function cleanTmp() {
        $this->messages = [];
        $this->polls = [];
        $this->chats = [];
        $this->members = [];
        $this->new_chat_members = [];
        $this->left_chat_member = [];
        $this->callback_queries = [];
    }

    /**
     * Parse Data for view
     * @param array $input each row data
     * @return array $output
     */
    public function parseData($input) {
        $this->logInfo(__METHOD__, 'input : '.json_encode($input));
        $this->input_data = $input;
        $type = $this->getUpdateType();
        $this->output_data = [
            'update_id' => isset($input['update_id']) ? $input['update_id'] : 0,
            'message_id' => $this->getMessageID($type),
            'message_text' => $this->getMessageText($type),
            'chat' => '',
            'type' => $type,
            'from' => '',
            'date' => '',
            'text' => $this->getText($type)
        ];

        $tmp = [];
        switch ($type) {
            case self::MESSAGE:
            case self::EDITED_MESSAGE:
            case self::CHANNEL_POST:
                $tmp = $this->parseMessage($input, $type);
                break;
            case 'my_chat_member':
                $tmp = $this->parseMyChatMember($input, $type);
                break;
            case self::CALLBACK_QUERY:
                $tmp = $this->parseCallbackQuery($input, $type);
                break;
            default:
                $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' output : ' . json_encode($this->output_data), true);
                exit;
        }

        foreach ($tmp as $key => $value) {
            $this->output_data[$key] = $value;
        }

        if ( ! empty($this->output_data['date'])) {
            $this->output_data['date'] = date('Y/m/d H:i:s', $tmp['date']);
        }
        $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' output : ' . json_encode($this->output_data));
        return $this->output_data;
    }

    /**
     * @param array $input
     * @param string $item parse item name
     * @param array $default default output
     * @return array $default
     */
    public function parseItem($input, $item, $default = []) {
        if ( ! isset($input[$item]) || empty($input[$item])) {
            return $default;
        }
        $maps = [];
        foreach (array_keys($default) as $key) {
            $maps[$key] = $key;
        }
        return $this->mergeItems($input[$item], $maps, $default);
    }

    /**
     * @param array $input
     * @param array $keys
     * @param array $default default output
     * @return array $default
     */
    public function mergeItems($input, $maps, $default = []) {
        foreach ($maps as $key => $trans) {
            if (isset($input[$key])) {
                $default[$trans] = $input[$key];
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
     * @param int $chat_id
     * @return array
     */
    public function parseOtherMember($input, $chat_id) {
        $output = [
            'old_chat_member' => false,
            'new_chat_member' => false,
            'left_chat_member' => false
        ];
        if (isset($input['old_chat_member']) && ! empty($input['old_chat_member'])) {
            $chat_member = $this->parseChatMember($input['old_chat_member'], $chat_id);
            $output['old_chat_member'] = true;
        }
        if (isset($input['new_chat_member']) && ! empty($input['new_chat_member'])) {
            $chat_member = $this->parseChatMember($input['new_chat_member'], $chat_id);
            if ( ! empty($chat_member['id'])) {
                $this->new_chat_members[$chat_id][$chat_member['id']] = $chat_member;
            }
            $output['new_chat_member'] = true;
        }
        if (isset($input['new_chat_members']) && ! empty($input['new_chat_members'])) {
            foreach ($input['new_chat_members'] as $new_chat_member) {
                $chat_member = $this->parseChatMember($new_chat_member, $chat_id);
                if ( ! empty($chat_member['id'])) {
                    $this->new_chat_members[$chat_id][$chat_member['id']] = $chat_member;
                }
            }
            $output['new_chat_member'] = true;
        }
        if (isset($input['left_chat_member']) && ! empty($input['left_chat_member'])) {
            $this->left_chat_member = $input['left_chat_member'];
            $output['left_chat_member'] = true;
        }

        $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' output : ' . json_encode($output));
        return $output;
    }

    /**
     * @param array $input
     * @param string $item parse item name
     * @return array
     */
    public function parseMessage($input, $type = 'message') {
        $output = [
            'id'            => '',
            'chat_id'       => '',
            'chat_name'     => '',
            'message_id'    => '',
            'message_text'  => $this->getMessageText($type),
            'from'          => '',
            'date'          => '',
            'text'          => '',
            'bot_name'      => $this->BotName
        ];
        if ( ! isset($input[$type]) || empty($input[$type])) {
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' type : ' . json_encode($type), true);
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' input : ' . json_encode($input), true);
            return $output;
        }
        $output = $this->mergeItems($input[$type], ['message_id'=>'id','date'=>'date','text'=>'text'], $output);

        $member = $this->parseMember($input[$type]);
        $output = $this->mergeItems($member , ['id'=>'member_id','from'=>'from'], $output);

        $chat = $this->parseChat($input[$type], 'chat');
        $output = $this->mergeItems($chat , ['id'=>'chat_id','title'=>'chat_name'], $output);

        if (empty($output['chat_name']) && isset($this->chats[$chat['id']]['title'])) {
            $output['chat_name'] = $this->chats[$chat['id']]['title'];
        }

        if (isset($input[$type]['sender_chat']) && ! empty($input[$type]['sender_chat'])) {
            $this->parseChat($input[$type], 'sender_chat');
        }

        $this->parseOtherMember($input[$type], $chat['id']);

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
        $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' output : ' . json_encode($output));
        return $output;
    }

    /**
     * https://github.com/Eleirbag89/TelegramBotPHP/blob/master/Telegram.php
     * Return current update type `False` on failure.
     *
     * @return bool|string
     */
    public function getUpdateType() {
        $update = $this->input_data;
        if (isset($update['inline_query'])) {
            return self::INLINE_QUERY;
        }
        if (isset($update['callback_query'])) {
            return self::CALLBACK_QUERY;
        }
        if (isset($update['edited_message'])) {
            return self::EDITED_MESSAGE;
        }
        if (isset($update['message']['text'])) {
            return self::MESSAGE;
        }
        if (isset($update['message']['photo'])) {
            return self::PHOTO;
        }
        if (isset($update['message']['video'])) {
            return self::VIDEO;
        }
        if (isset($update['message']['audio'])) {
            return self::AUDIO;
        }
        if (isset($update['message']['voice'])) {
            return self::VOICE;
        }
        if (isset($update['message']['contact'])) {
            return self::CONTACT;
        }
        if (isset($update['message']['location'])) {
            return self::LOCATION;
        }
        if (isset($update['message']['reply_to_message'])) {
            return self::REPLY;
        }
        if (isset($update['message']['animation'])) {
            return self::ANIMATION;
        }
        if (isset($update['message']['sticker'])) {
            return self::STICKER;
        }
        if (isset($update['message']['document'])) {
            return self::DOCUMENT;
        }
        if (isset($update['message']['new_chat_member'])) {
            return self::NEW_CHAT_MEMBER;
        }
        if (isset($update['message']['left_chat_member'])) {
            return self::LEFT_CHAT_MEMBER;
        }
        if (isset($update['channel_post'])) {
            return self::CHANNEL_POST;
        }
        if (isset($update['my_chat_member'])) {
            return 'my_chat_member';
        }
        return false;
    }

    /**
     * @param string $type
     * @return int
     */
    public function getMessageID($type) {
        if ($type == self::CALLBACK_QUERY) {
            return @$this->input_data['callback_query']['message']['message_id'];
        }
        if ($type == self::CHANNEL_POST) {
            return @$this->input_data['channel_post']['message_id'];
        }
        if ($type == self::EDITED_MESSAGE) {
            return @$this->input_data['edited_message']['message_id'];
        }
        return (int) $this->input_data['message']['message_id'];
    }
    /**
     * @param string $type
     * @return string
     */
    public function getMessageText($type) {
        if ($type == self::CALLBACK_QUERY) {
            return @$this->input_data['callback_query']['message']['text'];
        }
        if ($type == self::CHANNEL_POST) {
            return @$this->input_data['channel_post']['text'];
        }
        if ($type == self::EDITED_MESSAGE) {
            return @$this->input_data['edited_message']['text'];
        }
        return empty($this->input_data['message']['text']) ? '' : empty($this->input_data['message']['text']);
    }

    /**
     * @param string $type
     * @return string
     */
    public function getText($type) {
        if ($type == self::CALLBACK_QUERY) {
            return @$this->input_data['callback_query']['data'];
        }
        if ($type == self::CHANNEL_POST) {
            return @$this->input_data['channel_post']['text'];
        }
        if ($type == self::EDITED_MESSAGE) {
            return @$this->input_data['edited_message']['text'];
        }
        return @$this->input_data['message']['text'];
    }

    /**
     * @param string $type
     * @return int
     */
    public function getChatID($type) {
        if ($type == self::CALLBACK_QUERY) {
            return @$this->input_data['callback_query']['message']['chat']['id'];
        }
        if ($type == self::CHANNEL_POST) {
            return @$this->input_data['channel_post']['chat']['id'];
        }
        if ($type == self::EDITED_MESSAGE) {
            return @$this->input_data['edited_message']['chat']['id'];
        }
        if ($type == self::INLINE_QUERY) {
            return @$this->input_data['inline_query']['from']['id'];
        }
        return (int) $this->input_data['message']['chat']['id'];
    }

    /**
     * @param array $input
     * @param string $type parse item name
     * @return array
     */
    public function parseMyChatMember($input, $type = 'my_chat_member') {
        $output = ['id'=>'','chat_id'=>'','member_id'=> '','from'=>'','date'=>'','text'=>''];
        if ( ! isset($input[$type]) || empty($input[$type])) {
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' type : ' . json_encode($type), true);
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' input : ' . json_encode($input), true);
            return $output;
        }
        $output = $this->mergeItems($input[$type] , ['id'=>'id','date'=>'date','text'=>'text'], $output);

        $member = $this->parseMember($input[$type], 'from');
        $output = $this->mergeItems($member , ['id'=>'member_id','from'=>'from'], $output);

        $chat = $this->parseChat($input[$type], 'chat');
        $output = $this->mergeItems($chat , ['id'=>'chat_id'], $output);

        if (isset($input[$type]['sender_chat']) && ! empty($input[$type]['sender_chat'])) {
            $this->parseChat($input[$type], 'sender_chat');
        }

        $this->parseOtherMember($input[$type], $chat['id']);

        if (isset($input[$type]['new_chat_photo']) && ! empty($input[$type]['new_chat_photo'])) {
            foreach ($input[$type]['new_chat_photo'] as $photo) {
                // $output['text'] .= $photo['file_id'];
            }
        }

        if (isset($input[$type]['reply_to_message']) && ! empty($input[$type]['reply_to_message'])) {
            $reply_to_message = $this->parseMessage($input[$type], 'reply_to_message');
            $output['text'] = $reply_to_message['text'].' => '.$output['text'];
        }
        $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' output : ' . json_encode($output));
        return $output;
    }

    /**
     * Parse Callback Query
     * @param array $input
     * @param string $type parse item name
     * @return array
     */
    public function parseCallbackQuery($input, $type = self::CALLBACK_QUERY) {
        $output = [
            'id' => '',
            'chat_id' => $this->getChatID($type),
            'member_id' => '',
            'from' => '',
            'date' => '',
            'text' => '',
            'message_id' => $this->getMessageID($type),
            'message_text' => $this->getMessageText($type),
            'bot_name' => $this->BotName
        ];
        if ( ! isset($input[$type]) && empty($input[$type])) {
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' type : ' . json_encode($type), true);
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' input : ' . json_encode($input), true);
            return $output;
        }
        $output = $this->mergeItems($input[$type], ['id'=>'id','data'=>'text'], $output);
        $member = $this->parseMember($input[$type], 'from');
        $output = $this->mergeItems($member , ['id'=>'member_id','from'=>'from'], $output);
        $this->parseMessage($input[$type]);
        $this->callback_queries[$output['chat_id']][$output['member_id']][$output['text']] = $output;
        $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' output : ' . json_encode($output), true);
        return $output;
    }

    /**
     * Parse Callback Query
     * @param array $input
     * @param string $type parse item name
     * @return array
     */
    public function parsePoll($input, $type = 'poll') {
        $output = ['id'=>'','chat_id'=>'','member_id'=> '','from'=>'','date'=>'','text'=>''];
        if ( ! isset($input[$type]) || empty($input[$type])) {
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' type : ' . json_encode($type), true);
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' input : ' . json_encode($input), true);
            return $output;
        }
        $output = $this->mergeItems($input[$type], ['id'=>'id','date'=>'date'], $output);
        $member = $this->parseMember($input[$type], 'from');
        $output = $this->mergeItems($member , ['id'=>'member_id','from'=>'from'], $output);
        if (isset($input[$type]['question']) && ! empty($input[$type]['question'])) {
            $output['text'] .= $input[$type]['question'];
        }
        if (isset($input[$type]['options']) && ! empty($input[$type]['options'])) {
            $output['text'] .= json_encode($input[$type]['options']);
        }
        $this->polls[$output['id']] = $output;
        $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' output : ' . json_encode($output));
        return $output;
    }
}
