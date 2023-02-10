<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Longman\TelegramBot\Telegram;

use App\Models\TelegramBots;
use App\Models\Message;
use App\Models\User;
use App\Models\Chat;
use App\Models\UserChat;
use Illuminate\Filesystem\Cache as FilesystemCache;

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
    public $updates = [];
    public $input_data = [];
    public $output_data = [];
    public $logHead = '';
    public $last_update_id = 0;
    public $chat_members = [];

    private $telegram;

    // ===================== DB START ===================== //

    /**
     * @return array
     */
    public function bots() {
        $output = $this->getCache('bots');
        if ( ! empty($output)) {
            return $output;
        }
        $telegramBots = new TelegramBots();
        $output = $telegramBots::getListAll();
        if (empty($output)) {
            return $output;
        }
        $this->setCache('bot', $output);
        return $output;
    }

    /**
     * @return array
     */
    public function chats() {
        $output = $this->getCache('chats');
        if ( ! empty($output)) {
            return $output;
        }
        $chat = new Chat();
        $output = $chat::getListAll();
        if (empty($output)) {
            return $output;
        }
        $this->setCache('chat', $output);
        return $output;
    }

    /**
     * Select Mysql user
     * @return array
     */
    public function users() {
        $output = $this->getCache('users');
        if ( ! empty($output)) {
            return $output;
        }
        $user = new User();
        $output = $user::getListAll();
        if (empty($output)) {
            return $output;
        }
        $this->setCache('user', $output);
        return $output;
    }

    /**
     * @return mix
     */
    public function getCache($key) {
        $output = Cache::get($key);
        if ( ! empty($output) && ! is_array($output)) {
            $output = json_decode($output, true);
        }
        return $output;
    }

    /**
     * @return mix
     */
    public function setCache($key, $list = []) {
        Cache::put($key.'s', $list);
        foreach ($list as $row) {
            Cache::put($key.':'.$row['id'], $row);
        }
    }

    public function updateChat($new_chat) {
        if ( ! empty($new_chat['id'])) {
            return;
        }
        $old_chat = Cache::get('chat:'.$new_chat['id']);
        if (empty($old_chat)) {
            $chats = $this->chats();
            if (empty($chats[$new_chat['id']])) {
                // insert new chat
                $chat = new Chat();
                $new_chat['created_at'] = date('Y-m-d H:i:s');
                $new_chat['updated_at'] = date('Y-m-d H:i:s');
                $chat::insertData($new_chat);
                $this->logInfo(__METHOD__, 'LINE '.__LINE__.' new_chat '.var_export($new_chat, true));
                return;
            }
            $old_chat = $chats[$new_chat['id']];
        }
        $update = [];
        // check chat's value by column
        foreach (['type','title','username','first_name','last_name'] as $column) {
            if ($old_chat[$column] != $new_chat[$column]) {
                $update[$column] = $new_chat[$column];
            }
        }
        if ( ! empty($update)){
            // update old chat
            $chat = new Chat();
            $update['updated_at'] = date('Y-m-d H:i:s');
            $chat::updateData($new_chat['id'], $update);
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' update '.var_export($update, true));
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' old_chat '.var_export($old_chat, true));
        }
    }

    public function updateUser($new_user) {
        if ( ! empty($new_user['id'])) {
            return;
        }
        $old_user = Cache::get('user:'.$new_user['id']);
        if (empty($old_user)) {
            $users = $this->users();
            if (empty($users[$new_user['id']])) {
                // insert new user
                $user = new User();
                $new_user['created_at'] = date('Y-m-d H:i:s');
                $new_user['updated_at'] = date('Y-m-d H:i:s');
                $user::insertData($new_user);
                $this->logInfo(__METHOD__, 'LINE '.__LINE__.' new_user '.var_export($new_user, true));
                return;
            }
            $old_user = $users[$new_user['id']];
        }
        $update = [];
        // check chat's value by column
        foreach (['is_bot','username','first_name','last_name'] as $column) {
            if ($new_user[$column] != $old_user[$column]) {
                $update[$column] = $old_user[$column];
            }
        }
        if ( ! empty($update)){
            // update old chat
            $user = new User();
            $update['updated_at'] = date('Y-m-d H:i:s');
            $user::updateData($new_user['id'], $update);
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' update '.var_export($update, true));
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' old_user '.var_export($old_user, true));
        }
    }

    /**
     * Select Mysql chat
     * @return array
     */
    public function chatMessages($chat_id) {
        $message = new Message();
        return $message::getChatMessages($chat_id);
    }

    /**
     * Select Mysql user messages
     * @return array
     */
    public function userMessages($user_id) {
        $message = new Message();
        return $message::getUserMessages($user_id);
    }

    // ===================== DB END ===================== //

    // ===================== cURL telegram api START ===================== //

    /**
     * https://github.com/Eleirbag89/TelegramBotPHP/blob/bc0dd1d1e400b1d860b1fc111b988a25fa02857f/Telegram.php
     */
    public function endpoint($action, array $content) {
        $url = 'https://api.telegram.org/bot'.$this->ApiKey.'/'.$action;
        $reply = $this->_cURL($url, $content, $error);
        return json_decode($reply, true);
    }

    /**
     * cURL 略過檢查 SSL 憑證有效性
     * @param string    $url
     * @param array     $data
     * @param string    $error
     * @return string
     */
    private function _cURL($url, $data=[], &$error) {
        $time_start = microtime(true);
        $query = http_build_query($data);
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' url '.var_export($url, true).' data '.var_export($data, true));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 這裡略過檢查 SSL 憑證有效性
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        $output = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $time_end = microtime(true);
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' END '.var_export($output, true).' USED '.($time_end - $time_start).' s');
        if (empty($output)) {
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' error '. var_export($error, true));
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' info '. var_export($info, true));
            $output = json_encode(
                ['ok' => false, 'curl_error_code' => curl_errno($ch), 'curl_error' => curl_error($ch)]
            );
        }
        return $output;
    }

    /**
     * send chat members message
     * @param array $input
     * @return object $rs
     */
    public function sendMessage($input = []) {
        return $this->endpoint('sendMessage', $input);
    }

    /**
     * send chat members message
     * @param array $input
     * @return object $rs
     */
    public function editMessageText($input = []) {
        return $this->endpoint('editMessageText', $input);
    }

    /**
     * send chat members message
     * @param array $input
     * @return object $rs
     */
    public function editMessageReplyMarkup($input = []) {
        return $this->endpoint('editMessageReplyMarkup', $input);
    }

    /**
     * send chat members message
     * @param array $input
     * @return object $rs
     */
    public function deleteMessage($input = []) {
        return $this->endpoint('deleteMessage', $input);
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
        return $this->endpoint('restrictChatMember', [
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
        ]);
    }

    /**
     * Use this method to receive incoming updates using long polling.
     * @param int $offset Integer Identifier of the first update to be returned. Must be greater by one than the highest among the identifiers of previously received updates. By default, updates starting with the earliest unconfirmed update are returned. An update is considered confirmed as soon as getUpdates is called with an offset higher than its update_id.
     * @param int $limit Integer Limits the number of updates to be retrieved. Values between 1—100 are accepted. Defaults to 100
     * @param int $timeout Integer Timeout in seconds for long polling. Defaults to 0, i.e. usual short polling
     * @return array the updates as Array.
     */
    public function getUpdates($offset = 0, $limit = 100, $timeout = 0) {
        $content = ['offset' => $offset, 'limit' => $limit, 'timeout' => $timeout];
        return $this->endpoint('getUpdates', $content);
    }


    // ===================== cURL telegram api END ===================== //

    // ===================== parse START ===================== //

    /**
     * https://github.com/Eleirbag89/TelegramBotPHP/blob/bc0dd1d1e400b1d860b1fc111b988a25fa02857f/Telegram.php
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
     * https://github.com/Eleirbag89/TelegramBotPHP/blob/bc0dd1d1e400b1d860b1fc111b988a25fa02857f/Telegram.php
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
     * https://github.com/Eleirbag89/TelegramBotPHP/blob/bc0dd1d1e400b1d860b1fc111b988a25fa02857f/Telegram.php
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
     * https://github.com/Eleirbag89/TelegramBotPHP/blob/bc0dd1d1e400b1d860b1fc111b988a25fa02857f/Telegram.php
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
     * @return array
     */
    public function getContact() {
        $contact = [
            'phone_number'  => '',
            'first_name'    => '',
            'last_name'     => '',
            'user_id'       => 0,
        ];
        if ( ! empty($this->input_data['message']['contact'])) {
            foreach ($this->input_data['message']['contact'] as $key => $value) {
                $contact[$key] = $value;
            }
        }
        return $contact;
    }

    /**
     * Parse Data for view
     * @param array $input each row data
     * @return array $output
     */
    public function parseResult($input) {
        // $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' input : '.var_export($input, true));
        $this->cleanTmp();
        // $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' last_update_id : '.var_export($this->last_update_id, true));
        $output = [];
        foreach ((array)$input as $row) {
            $tmp = $this->parseData((array)$row);
            $output[] = $tmp;
        }
        foreach ($this->messages as $message) {
            $this->messageEvent($message);
        }
        foreach ($this->new_chat_members as $chat_members) {
            foreach ($chat_members as $member) {
                $this->newChatMembersEvent($member);
            }
        }
        foreach ($this->callback_queries as $chat_callback) {
            foreach ($chat_callback as $callback) {
                $this->callbackEvent($callback);
            }
        }
        // foreach ($this->chats as $chat) {
        //     $this->updateChat($chat);
        // }
        foreach ($this->members as $member) {
            $this->updateUser($member);
        }
        // $user_chat = new UserChat();
        // foreach ($this->chat_members as $chat_id => $chat_members) {
        //     if (empty($chat_id)) {
        //         continue;
        //     }
        //     foreach ($chat_members as $user_id) {
        //         if (empty($user_id)) {
        //             continue;
        //         }
        //         $exit = $user_chat::getOne($user_id, $chat_id);
        //         if (empty($exit)) {
        //             $new = [
        //                 'user_id' => $user_id,
        //                 'chat_id' => $chat_id
        //             ];
        //             $user_chat::insertData($new);
        //             $this->logInfo(__METHOD__, 'LINE '.__LINE__.' insertData '.var_export($new, true));
        //         }
        //     }
        // }
        // $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' last_update_id : '.var_export($this->last_update_id, true));
        // $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' output : '.var_export($output, true));
        return $output;
    }

    /**
     * Parse Data for view
     * @param array $input each row data
     * @return array $output
     */
    public function parseData($input) {
        $this->input_data = $input;
        $type = $this->getUpdateType();
        $this->last_update_id = isset($input['update_id']) ? (int) $input['update_id'] : 0;
        Cache::put('last_update_id:'.$this->BotName, $this->last_update_id);
        $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' last_update_id : '.var_export($this->last_update_id, true));
        $this->output_data = [
            'update_id'     => $this->last_update_id,
            'message_id'    => $this->getMessageID($type),
            'message_text'  => $this->getMessageText($type),
            'chat'          => '',
            'type'          => $type,
            'from'          => '',
            'date'          => '',
            'text'          => $this->getText($type)
        ];
        $tmp = [];
        switch ($type) {
            case self::MESSAGE:
            case self::EDITED_MESSAGE:
            case self::CHANNEL_POST:
                $tmp = $this->parseMessage($input, $type);
                break;
            case self::CALLBACK_QUERY:
                $tmp = $this->parseCallbackQuery($input, $type);
                break;
            case 'my_chat_member':
                // $tmp = $this->parseMyChatMember($input, $type);
                // break;
            default:
                $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' output : ' . var_export($this->output_data, true));
                break;
        }
        foreach ($tmp as $key => $value) {
            $this->output_data[$key] = $value;
        }
        if ( ! empty($this->output_data['date'])) {
            $this->output_data['date'] = date('Y/m/d H:i:s', $tmp['date']);
        }
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
        $this->chat_members[$chat_id][$output['id']] = $output['id'];
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
            'chat_id'       => $this->getChatID($type),
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
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' input : ' . var_export($input, true));
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
        // if ( ! empty($input[$type]['reply_to_message'])) {
        //     $reply_to_message = $this->parseMessage($input[$type], 'reply_to_message');
        //     $output['text'] = $reply_to_message['text'].' => '.$output['text'];
        // }
        return $output;
    }

    /**
     * @param array $input
     * @param string $type parse item name
     * @return array
     */
    public function parseMyChatMember($input, $type = 'my_chat_member') {
        $output = [
            'id'        => '',
            'chat_id'   => $this->getChatID($type),
            'member_id' => '',
            'from'      => '',
            'date'      => '',
            'text'      => '',
            'bot_name'  => $this->BotName
        ];
        if ( ! isset($input[$type]) || empty($input[$type])) {
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' type : ' . json_encode($type), true);
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' input : ' . var_export($input, true));
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
        // if (isset($input[$type]['reply_to_message']) && ! empty($input[$type]['reply_to_message'])) {
        //     $reply_to_message = $this->parseMessage($input[$type], 'reply_to_message');
        //     $output['text'] = $reply_to_message['text'].' => '.$output['text'];
        // }
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
            'id'            => '',
            'chat_id'       => $this->getChatID($type),
            'member_id'     => '',
            'from'          => '',
            'date'          => '',
            'text'          => '',
            'message_id'    => $this->getMessageID($type),
            'message_text'  => $this->getMessageText($type),
            'bot_name'      => $this->BotName
        ];
        if ( ! isset($input[$type]) && empty($input[$type])) {
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' type : ' . json_encode($type), true);
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' input : ' . var_export($input, true));
            return $output;
        }
        $output = $this->mergeItems($input[$type], ['id'=>'id','data'=>'text'], $output);
        $member = $this->parseMember($input[$type], 'from');
        $output = $this->mergeItems($member , ['id'=>'member_id','from'=>'from'], $output);
        // $this->parseMessage($input[$type]);
        $this->callback_queries[$output['chat_id']][$output['message_id']] = $output;
        return $output;
    }

    /**
     * Parse Callback Query
     * @param array $input
     * @param string $type parse item name
     * @return array
     */
    public function parsePoll($input, $type = 'poll') {
        $output = ['id'=>'','chat_id'=>$this->getChatID($type),'member_id'=> '','from'=>'','date'=>'','text'=>''];
        if ( ! isset($input[$type]) || empty($input[$type])) {
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' type : ' . json_encode($type), true);
            $this->logInfo(__METHOD__, 'LINE : '.__LINE__.' input : ' . var_export($input, true));
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

    // ===================== parse END ===================== //

    public function logInfo($method = '', $info = '', $echo = false) {
        $log = date('Y-m-d H:i:s') . ' ' .$method. ' '.$info;
        Log::debug($log);
        if ( ! empty($echo)) {
            echo $log . PHP_EOL ;
        }
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
        $this->last_update_id = (int) Cache::get('last_update_id:'.$BotName);
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
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' ['.$BotName.'] START');

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
            $time_end = microtime(true);
            if ( ! isset($rs->ok) || $rs->ok !== true) {
                $this->logInfo(__METHOD__, 'LINE '.__LINE__.' ['.$BotName.'] ERROR '.var_export($rs, true).' USED '.($time_end - $time_start) . ' s');
                return false;
            }
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' ['.$BotName.'] END '.var_export($rs, true).' USED '.($time_end - $time_start) . ' s');
            return $rs;
        } catch (\Longman\TelegramBot\Exception\TelegramException $e) {
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' ['.$BotName.'] ERROR '. json_encode($e->getMessage()), true);
        }
    }

    /**
     * cURL TelegramBot API getUpdates and parse response for feedback
     * @param string $BotName Bot's username
     * @param int $limit
     * @param int $timeout
     * @return array
     */
    public function readGetUpdates($BotName, $limit = 100, $timeout = 0) {
        $output = [];
        $time_start = microtime(true);
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' ['.$BotName.'] START');
        $this->getToken($BotName);
        $response = $this->getUpdates($this->last_update_id, $limit, $timeout);
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' response '.var_export($response, true));
        $result = ! empty($response['result']) ? (array) $response['result'] : [];
        if ( ! empty($result)) {
            $list = $this->parseResult($result);
            $output += $list;
            // update telegram last_update_id
            $response = $this->getUpdates($this->last_update_id+1, 1, 0);
            $result = ! empty($response['result']) ? (array) $response['result'] : [];
            if ( ! empty($result)) {
                $list = $this->parseResult($result);
                $output += $list;
            }
        }
        $time_end = microtime(true);
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' USED '.($time_end - $time_start) . ' s', true);
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' output '.var_export($output, true));
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

    public function messageEvent($message) {
        if (empty($message)) {
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' ERROR message : ' . var_export($message, true), true);
            return;
        }
        $bots = [];
        foreach ($this->bots() as $bot) {
            if (empty($bot['user_id'])) {
                continue;
            }
            $bots[$bot['user_id']] = $bot['username'];
        }
        if ( ! empty($bots[$message['member_id']])) {
            // message from bots
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' bot\'s message : ' . var_export($message, true));
            return;
        }
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' message : ' . var_export($message, true));
        $text = ! empty($message['text']) ? strtolower($message['text']) : '';
        if ( ! empty($text) && ! empty($message['chat_id'])) {
            if (strpos($text, 'are you bot') !== false ) {
                $this->sendMessage([
                    'chat_id' => $message['chat_id'],
                    'text' => 'Yes, @' . $this->BotName . ' is a bot.'
                ]);
            }
            if ($message['chat_id'] == $message['member_id'] && $text == '/start') {
                // message from private group
                $message_text  = "Buy, sell, store and pay with cryptocurrency whenever you want.\n";
                $message_text .= "\n";
                $message_text .= "⚠️ This is the testnet version of @" . $this->BotName;
                $this->sendMessage([
                    'chat_id'       => $message['chat_id'],
                    'text'          => $message_text,
                    'parse_mode'    => 'HTML',
                    'reply_markup'  => json_encode([
                        'inline_keyboard' => [
                            [['text'=>'Wallet','callback_data'=>'/Wallet'],['text'=>'Subscriptions','callback_data'=>'/Subscriptions']],
                            [['text'=>'Market','callback_data'=>'/Market'],['text'=>'Exchange','callback_data'=>'/Exchange']],
                            [['text'=>'Checks','callback_data'=>'/Checks'],['text'=>'Invoices','callback_data'=>'/Invoices']],
                            [['text'=>'Pay','callback_data'=>'/Pay'],['text'=>'Contacts','callback_data'=>'/Contacts']],
                            [['text'=>'Settings','callback_data'=>'/Settings']]
                        ],
                    ])
                ]);
            }
        }
    }

    public function newChatMembersEvent($member) {
        if (empty($member) || empty($member['id']) || empty($member['chat_id'])) {
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' ERROR member : ' . var_export($member, true));
            return;
        }
        $bots = [];
        foreach ($this->bots() as $bot) {
            if (empty($bot['user_id'])) {
                continue;
            }
            $bots[$bot['user_id']] = $bot['username'];
        }
        if ( ! empty($bots[$member['id']])) {
            // event from bots
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' bot\'s event : ' . var_export($member, true));
            return;
        }
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' member : ' . var_export($member, true), true);
        // set all permissions false
        $this->restrictChatMember($member, false);
        $text = '';
        if ( ! empty($member['first_name'])) {
            $text .= $member['first_name'].' ';
        } else if ( ! empty($member['username'])) {
            $text .= '@'.$member['username'].' ';
        }
        $text .= "歡迎到 本社群，請維持禮貌和群友討論，謝謝！\n";
        $text .= "進到群組請先觀看我們的群組導航，裡面可以解決你大多數的問題\n";
        $text .= "\n\n";
        $text .= "新進來的朋友記得點一下 “👉🏻解禁我👈🏻”\n";
        $text .= "來不及點到的、無法發言的，請退群重加";
        $data = [
            'chat_id'       => $member['chat_id'],
            'text'          => $text,
            'parse_mode'    => 'HTML',
            'reply_markup'  => json_encode([
                'inline_keyboard' => [[['text'=>'👉🏻解禁我👈🏻','callback_data'=>'/un_mute:'.$member['id']]]],
            ])
        ];
        $sendResult = $this->sendMessage($data);
        if ( ! empty($sendResult) && $sendResult['ok'] === true) {
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' Message sent to: ' . $member['chat_id']);
        } else {
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' Sorry message not sent to: ' . $member['chat_id']);
        }
    }

    public function callbackEvent($callback) {
        if (empty($callback)
            || empty($callback['chat_id'])
            || empty($callback['member_id'])
            || empty($callback['message_id'])
            || empty($callback['text'])) {
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' ERROR callback : ' . var_export($callback, true), true);
            return;
        }
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' callback : ' . var_export($callback, true), true);
        $member = Cache::get('user:'.$callback['member_id']);
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' member : ' . var_export($member, true), true);

        $language_code = Cache::get('language_code:'.$callback['member_id']);
        if (empty($language_code)) {
            if (empty($member['language_code'])) {
                $language_code = 'en';
            } else {
                $language_code = strpos('zh', $member['language_code']) !== false ? 'zh' : 'en';
            }
            Cache::put('language_code:'.$callback['member_id'], $language_code);
        }
        $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' language_code : ' . var_export($language_code, true), true);

        $lang = $this->_getLang($language_code);
        $text  = $lang['default_text'];
        $reply_markup = [
            'inline_keyboard' => [
                [['text'=>$lang['back'],'callback_data'=>'/start']]
            ],
        ];

        $data = strtolower($callback['text']);
        $explode = explode(':', $data);
        $data = $explode[0];
        switch ($data) {
            case '/un_mute':
                $from_user = (int) $explode[1];
                if ($from_user == $callback['member_id']) {
                    $this->restrictChatMember([
                        'id'        => $callback['member_id'],
                        'chat_id'   => $callback['chat_id'],
                    ], true);
                    $this->deleteMessage([
                        'chat_id'   => $callback['chat_id'],
                        'message_id'=> $callback['message_id'],
                    ]);
                }
                break;
            case '/start':
                $reply_markup = [
                    'inline_keyboard' => [
                        // [['text'=>'Wallet','callback_data'=>'/wallet'],['text'=>'Subscriptions','callback_data'=>'/subscriptions']],
                        // [['text'=>'Market','callback_data'=>'/market'],['text'=>'Exchange','callback_data'=>'/exchange']],
                        // [['text'=>'Checks','callback_data'=>'/checks'],['text'=>'Invoices','callback_data'=>'/invoices']],
                        // [['text'=>'Pay','callback_data'=>'/pay'],['text'=>'Contacts','callback_data'=>'/contacts']],
                        [['text'=>$lang['wallet'],'callback_data'=>'/wallet']],
                        [['text'=>$lang['settings'],'callback_data'=>'/settings']]
                    ]
                ];
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/wallet':
                $text = "👛 ".$lang['wallet']."\n\n";
                $text .= "· Tether: 50 USDT ($50)\n\n";
                $text .= "· Toncoin: 0 TON\n\n";
                $text .= "· Bitcoin: 0 BTC\n\n";
                $text .= "· Ethereum: 0 ETH\n\n";
                $text .= "· Binance Coin: 0 BNB\n\n";
                $text .= "· Binance USD: 0 BUSD\n\n";
                $text .= "· USD Coin: 0 USDC\n\n";
                $text .= "≈ 0.00215923 BTC ($50)";
                $entities =  array (
                    array (
                      'offset' => 3,
                      'length' => 6,
                      'type' => 'bold',
                    ),
                    array (
                      'offset' => 13,
                      'length' => 6,
                      'type' => 'text_link',
                      'url' => 'https://tether.to/',
                    ),
                    array (
                      'offset' => 13,
                      'length' => 6,
                      'type' => 'bold',
                    ),
                    array (
                      'offset' => 38,
                      'length' => 7,
                      'type' => 'text_link',
                      'url' => 'https://ton.org/',
                    ),
                    array (
                      'offset' => 38,
                      'length' => 7,
                      'type' => 'bold',
                    ),
                    array (
                      'offset' => 56,
                      'length' => 7,
                      'type' => 'text_link',
                      'url' => 'https://bitcoin.org/',
                    ),
                    array (
                      'offset' => 56,
                      'length' => 7,
                      'type' => 'bold',
                    ),
                    array (
                      'offset' => 74,
                      'length' => 8,
                      'type' => 'text_link',
                      'url' => 'https://ethereum.org/',
                    ),
                    array (
                      'offset' => 74,
                      'length' => 8,
                      'type' => 'bold',
                    ),
                    array (
                      'offset' => 93,
                      'length' => 12,
                      'type' => 'text_link',
                      'url' => 'https://binance.org/',
                    ),
                    array (
                      'offset' => 93,
                      'length' => 12,
                      'type' => 'bold',
                    ),
                    array (
                      'offset' => 116,
                      'length' => 11,
                      'type' => 'text_link',
                      'url' => 'https://www.binance.com/en/busd',
                    ),
                    array (
                      'offset' => 116,
                      'length' => 11,
                      'type' => 'bold',
                    ),
                    array (
                      'offset' => 139,
                      'length' => 8,
                      'type' => 'text_link',
                      'url' => 'https://www.centre.io/usdc',
                    ),
                    array (
                      'offset' => 139,
                      'length' => 8,
                      'type' => 'bold',
                    ),
                );
                $reply_markup = [
                    'inline_keyboard' => [
                        [['text'=>$lang['deposit'],'callback_data'=>'/deposit'],['text'=>$lang['withdraw'],'callback_data'=>'/withdraw']],
                        [['text'=>$lang['back'],'callback_data'=>'/start']]
                    ],
                ];
                $this->_editMessage($callback, $text, $reply_markup, $entities);
                break;
            case '/deposit':    // Wallet > Deposit
            case '/withdraw':   // Wallet > Withdraw
                $text = $lang['select_currency'];
                $reply_markup = [
                    'inline_keyboard' => [
                        [['text'=>'BTC','callback_data'=>$data.':btc']],
                        [['text'=>'ETH','callback_data'=>$data.':eth']],
                        [['text'=>$lang['back_wallet'],'callback_data'=>'/wallet']]
                    ],
                ];
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/settings':
                $text  = "👤 Icky Maiasaura\n\n".$lang['your_language'].": ";
                if ($language_code == 'zh') {
                    $text .= $lang['language_zh'];
                } else {
                    $text .= $lang['language_en'];
                }
                $entities = array (
                    array (
                        'offset' => 3,
                        'length' => 14,
                        'type' => 'text_link',
                        'url' => 'http://t.me/'.$this->BotName,
                    ),
                    array (
                        'offset' => 3,
                        'length' => 14,
                        'type' => 'bold',
                    ),
                );
                $reply_markup = [
                    'inline_keyboard' => [
                        // [['text'=>'Referral Program','callback_data'=>'/settings_referral']],
                        // [['text'=>'Notifications','callback_data'=>'/settings_notifications']],
                        [['text'=>$lang['language'],'callback_data'=>'/settings_language']],
                        [['text'=>$lang['contact_us'],'url'=>'https://www.100ex.com/zh_CN/cms/contact%20us']],
                        [['text'=>$lang['back'],'callback_data'=>'/Start']]
                    ],
                ];
                $this->_editMessage($callback, $text, $reply_markup, $entities);
                break;
            case '/settings_language': // Settings > Language
                $text  = 'Please choose a language.';
                $reply_markup = [
                    'inline_keyboard' => [
                        [['text'=>$lang['language_zh'],'callback_data'=>'/set_language:zh']],[['text'=>$lang['language_en'],'callback_data'=>'/set_language:en']],
                        [['text'=>$lang['back_settings'],'callback_data'=>'/settings']]
                    ]
                ];
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/set_language': // Settings > Language > set language
                $new_code = empty($explode[1]) || $explode[1] != 'zh' ? 'en' : 'zh';
                $lang = $this->_getLang($new_code);
                Cache::put('language_code:'.$callback['member_id'], $new_code);
                $text  = $lang['default_text'];
                $reply_markup = [
                    'inline_keyboard' => [
                        [['text'=>$lang['wallet'],'callback_data'=>'/wallet']],
                        [['text'=>$lang['settings'],'callback_data'=>'/settings']]
                    ],
                ];
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/subscriptions':
                $text = 'Chat message subscription, not related to trading business.';
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/market':
                $reply_markup = [
                    'inline_keyboard' => [
                        [['text'=>'Buy','callback_data'=>'/Buy'],['text'=>'Sell','callback_data'=>'/Sell']],
                        [['text'=>'Back','callback_data'=>'/Start']]
                    ],
                ];
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/buy':        // Market > Buy
            case '/sell':       // Market > Sell
                $text = 'Please select a crypto currency.';
                $reply_markup = [
                    'inline_keyboard' => [
                        [['text'=>'BTC','callback_data'=>$data.'_btc']],
                        [['text'=>'ETH','callback_data'=>$data.'_eth']],
                        [['text'=>'Back to Market','callback_data'=>'/Market']]
                    ],
                ];
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/pay':
                $text = 'Please select a crypto currency.';
                $reply_markup = [
                    'inline_keyboard' => [
                        [['text'=>'BTC','callback_data'=>$data.'_btc']],
                        [['text'=>'ETH','callback_data'=>$data.'_eth']],
                        [['text'=>'Back','callback_data'=>'/Start']]
                    ],
                ];
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/exchange':
                $text  = "🐬 Here you can exchange cryptocurrencies using limit orders that executed automatically.\n";
                $text .= "\n";
                $text .= "🪐 Create your order to start. 0.75% fee for takers and 0.5% fee for makers.";
                $reply_markup = [
                    'inline_keyboard' => [
                        [['text'=>'Exchange Now','callback_data'=>'/do_exchange']],
                        [['text'=>'Order History','callback_data'=>'/order_history']],
                        [['text'=>'Back','callback_data'=>'/Start']]
                    ],
                ];
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/do_exchange': // Exchange > Exchange Now
                $text  = 'Choose cryptocurrencies you want to exchange.';
                $reply_markup = [
                    'inline_keyboard' => [
                        [['text'=>'BTC/USDT','callback_data'=>$data.'_btc']],
                        [['text'=>'ETH/USDT','callback_data'=>$data.'_eth']],
                        [['text'=>'Back to Exchange','callback_data'=>'/exchange']]
                    ],
                ];
                $this->_editMessage($callback, $text, $reply_markup);
                break;
            case '/checks':
            case '/invoices':
            case '/contacts':
                $this->_editMessage($callback, $text, $reply_markup);
                break;
        }
    }

    private function _getLang($language_code) {
        if ($language_code == 'zh') {
            return [
                'default_text'      => "随时随地使用加密货币购买、出售、存储和支付。\n\n⚠️ 这是测试网版本 @" . $this->BotName,
                'back'              => '返回',
                'wallet'            => '钱包',
                'deposit'           => '存币',
                'withdraw'          => '提币',
                'back_wallet'       => '返回钱包',
                'select_currency'   => '选择币种',
                'settings'          => '设置',
                'language'          => '语言',
                'back_settings'     => '返回设置',
                'your_language'     => '你的语言',
                'language_zh'       => "🇨🇳 中文",
                'language_en'       => "🇺🇸 English",
                'contact_us'        => '联系我们'
            ];
        }
        return [
            'default_text'      => "Buy, sell, store and pay with cryptocurrency whenever you want.\n\n⚠️ This is the testnet version of @" . $this->BotName,
            'back'              => 'Back',
            'wallet'            => 'Wallet',
            'deposit'           => 'Deposit',
            'withdraw'          => 'Withdraw',
            'back_wallet'       => 'Back to Wallet',
            'select_currency'   => 'Please select a crypto currency.',
            'settings'          => 'Settings',
            'language'          => 'Language',
            'back_settings'     => 'Back to Settings',
            'your_language'     => 'Your Language',
            'language_zh'       => "🇨🇳 中文",
            'language_en'       => "🇺🇸 English",
            'contact_us'        => 'Contact Us'
        ];
    }

    /**
     * edit Message text and keyboard
     *
     * @param array $callback
     * @param string $text
     * @param string $keyboard
     * @return void
     */
    private function _editMessage($callback, $text, $reply_markup = '', $entities = []) {
        if ($callback['message_text'] != $text) {
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' editMessageText callback : ' . var_export($callback, true), true);
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' editMessageText text : ' . var_export($text, true), true);
            $data = [
                'chat_id'       => $callback['chat_id'],
                'message_id'    => $callback['message_id'],
                'text'          => $text,
                // 'parse_mode'    => 'HTML'
            ];
            if ( ! empty($reply_markup)) {
                $data['reply_markup'] = json_encode($reply_markup);
            }
            if ( ! empty($entities)) {
                $data['entities'] = json_encode($entities);
            }
            $this->editMessageText($data);
        } else if ( ! empty($reply_markup)) {
            $this->logInfo(__METHOD__, 'LINE '.__LINE__.' '.$this->logHead.' editMessageReplyMarkup : ' . var_export($reply_markup, true), true);
            $this->editMessageReplyMarkup([
                'chat_id'       => $callback['chat_id'],
                'message_id'    => $callback['message_id'],
                'reply_markup'  => json_encode($reply_markup)
            ]);
        }
    }
}
