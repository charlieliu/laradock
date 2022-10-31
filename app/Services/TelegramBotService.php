<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

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
            $output[] = $tmp;
        }

        return $output;
    }

    /**
     * Select Mysql chat
     * @return array
     */
    public function chats() {
        $output = [];
        $response = (array) DB::select('select * from `chat`');
        foreach ($response as $row) {
            $output[] = (array) $row;
        }
        return $output;
    }

    /**
     * Select Mysql chat
     * @return array
     */
    public function chat_messages($id) {
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
    public function user_messages($id) {
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
     * cURL TelegramBot API getUpdates
     * @param array $name Bot's username
     * @return array
     */
    public function getUpdates($name) {
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
        $this->messages = $this->polls = $this->chats = $this->members = $this->new_chat_members = $this->left_chat_member = [];
        $list = [];
        foreach ($response['result'] as $row) {
            $list[] = $this->parse_data($row);
        }
        return $list;
    }

    /**
     * Parse Data for view
     * @param array $input each row data
     * @return array $output
     */
    public function parse_data($input) {
        $output = [
            'update_id' => isset($input['update_id']) ? $input['update_id'] : 0,
            'type' => '',
            'from' => '--',
            'date' => '----/--/--',
            'text' => ''
        ];

        // message
        if ( ! empty($input['message'])) {
            $output['type'] = 'message';
            $message = $this->parse_message($input);
            $output['text'] = $message['text'];
            if ( ! empty($message['date'])) {
                $output['date'] = date('Y/m/d H:i:s', $message['date']);
            }
            if ( ! empty($message['from'])) {
                $output['from'] = $message['from'];
            }
            return $output;
        }

        if ( ! empty($input['edited_message'])) {
            $output['type'] = 'edited_message';
            $message = $this->parse_message($input, 'edited_message');
            $output['text'] = $message['text'];
            if ( ! empty($message['date'])) {
                $output['date'] = date('Y/m/d H:i:s', $message['date']);
            }
            if ( ! empty($message['from'])) {
                $output['from'] = $message['from'];
            }
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
            return $output;
        }

        // channel
        if ( ! empty($input['channel_post'])) {
            $output['type'] = 'channel_post';
            $message = $this->parse_message($input, 'channel_post');
            if ( ! empty($message['from'])) {
                $output['from'] = $message['from'];
            }
            return $output;
        }

        // chat member
        if ( ! empty($input['my_chat_member'])) {
            $output['type'] = 'my_chat_member';
            $message = $this->parse_my_chat_member($input);
            if ( ! empty($message['from'])) {
                $output['from'] = $message['from'];
            }
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
    public function parse_item($input, $item, $default) {
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
    public function parse_chat($input, $item = 'chat') {
        $output = $this->parse_item($input, $item, $this->default_chat);
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
    public function parse_member($input, $item = 'from') {
        $output = $this->parse_item($input, $item, $this->default_member);
        if ( ! empty($output['id'])) {
            $this->members[$output['id']] = $output;
        }
        return $output;
    }

    /**
     * @param array $input
     * @param string $item parse item name
     * @return array
     */
    public function parse_chat_member($input) {
        $member = $this->parse_member($input, 'user');
        $input['id'] = $member['id'];
        $input['is_bot'] = $member['is_bot'];
        return $input;
    }

    /**
     * @param array $input
     * @param string $item parse item name
     * @return array
     */
    public function parse_message($input, $type = 'message') {
        if (empty($input[$type])) {
            echo __METHOD__.' LINE : '.__LINE__.' '.json_encode($input);exit;
        }
        $member = $this->parse_member($input[$type]);
        $chat = $this->parse_chat($input[$type], 'chat');
        $output = [
            'id' => $input[$type]['message_id'],
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
            $this->parse_chat($input[$type], 'sender_chat');
        }
        if ( ! empty($input[$type]['old_chat_member'])) {
            $chat_member = $this->parse_chat_member($input[$type]['old_chat_member'], $chat['id']);
        }
        if ( ! empty($input[$type]['new_chat_member'])) {
            $chat_member = $this->parse_chat_member($input[$type]['new_chat_member'], $chat['id']);
            $this->new_chat_members[$chat_member['id']] = $chat_member;
        }
        if ( ! empty($input[$type]['new_chat_members'])) {
            foreach ($input[$type]['new_chat_members'] as $new_chat_member) {
                $chat_member = $this->parse_chat_member($new_chat_member, $chat['id']);
                $this->new_chat_members[$new_chat_member['id']] = $new_chat_member;
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
            $reply_to_message = $this->parse_message($input[$type], 'reply_to_message');
            $output['text'] = $reply_to_message['text'].' => '.$output['text'];
        }
        return $output;
    }

    /**
     * @param array $input
     * @param string $type parse item name
     * @return array
     */
    public function parse_my_chat_member($input, $type = 'my_chat_member') {
        $member = $this->parse_member($input[$type]);
        $chat = $this->parse_chat($input[$type], 'chat');
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
            $this->parse_chat($input[$type], 'sender_chat');
        }
        if ( ! empty($input[$type]['old_chat_member'])) {
            $chat_member = $this->parse_chat_member($input[$type]['old_chat_member'], $chat['id']);
        }
        if ( ! empty($input[$type]['new_chat_member'])) {
            $chat_member = $this->parse_chat_member($input[$type]['new_chat_member'], $chat['id']);
            $this->new_chat_members[$chat_member['id']] = $chat_member;
        }
        if ( ! empty($input[$type]['new_chat_members'])) {
            foreach ($input[$type]['new_chat_members'] as $new_chat_member) {
                $chat_member = $this->parse_chat_member($new_chat_member, $chat['id']);
                $this->new_chat_members[$new_chat_member['id']] = $new_chat_member;
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
                // $output['text'] .= $photo['file_id'];
            }
        }
        if ( ! empty($input[$type]['reply_to_message'])) {
            $reply_to_message = $this->parse_message($input[$type], 'reply_to_message');
            $output['text'] = $reply_to_message['text'].' => '.$output['text'];
        }
        return $output;
    }
}
