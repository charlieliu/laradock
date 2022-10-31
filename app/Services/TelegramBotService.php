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
        // read Mysql bot data
        $users = [];
        $response = (array) DB::select('SELECT * FROM `user` WHERE is_bot = 1');
        foreach ($response as $row) {
            $tmp = $this->default_member;
            $row = (array) $row;
            foreach ($row as $key => $value) {
                if ( ! is_null($value) || is_bool($value)) {
                    $tmp[$key] = $value;
                }
            }
            $users[$tmp['username']] = $tmp;
        }

        // read Mysql bots
        $output = [];
        $response = (array) DB::select('select * from `telegram_bots`');
        foreach ($response as $row) {
            $tmp = (array) $row;

            // set default column
            foreach (['user_id','first_name','last_name'] as $column) {
                $tmp[$column] = '';
            }

            // marge user data
            if ( ! empty($users[$tmp['username']])) {
                $tmp['user_id']     = $users[$tmp['username']]['id'];
                $tmp['first_name']  = $users[$tmp['username']]['first_name'];
                $tmp['last_name']   = $users[$tmp['username']]['last_name'];
            }
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
            print_r($response);
            exit;
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
            'update_id' => $input['update_id'],
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
