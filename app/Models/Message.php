<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class Message extends Model
{
    use HasFactory;

    public static function logInfo($method = '', $info = '') {
        $log = date('Y-m-d H:i:s') . ' ' .$method. ' '.$info;
        Log::debug($log);
    }

    public static function columns() {
        return [
            'PK' => ['chat_id','id'],
            'INT' => [
                'sender_chat_id',
                'user_id',
                'forward_from',
                'forward_from_chat',
                'forward_from_message_id',
                'is_automatic_forward',
                'reply_to_chat',
                'reply_to_message',
                'via_bot',
                'has_protected_content',
                'left_chat_member',
                'delete_chat_photo',
                'group_chat_created',
                'supergroup_chat_created',
                'channel_chat_created',
                'migrate_to_chat_id',
                'migrate_from_chat_id',
            ],
            'TEXT' => [
                'forward_signature',
                'forward_sender_name',
                'media_group_id',
                'author_signature',
                'text',
                'entities',
                'caption_entities',
                'audio',
                'document',
                'animation',
                'game',
                'photo',
                'sticker',
                'video',
                'voice',
                'video_note',
                'caption',
                'contact',
                'location',
                'venue',
                'poll',
                'dice',
                'new_chat_members',
                'new_chat_photo',
                'new_chat_photo',
                'message_auto_delete_timer_changed',
                'pinned_message',
                'invoice',
                'successful_payment',
                'connected_website',
                'passport_data',
                'proximity_alert_triggered',
                'video_chat_scheduled',
                'video_chat_started',
                'video_chat_ended',
                'video_chat_participants_invited',
                'web_app_data',
                'reply_markup',
                'new_chat_title'
            ],
            'TIMESTAMP' => [
                'date',
                'forward_date',
                'edit_date',
            ]
        ];
    }

    public static function getOne($id = 0) {
        return DB::table('message')->where('id', $id)->first();
    }

    public static function getChatMessages($chat_id) {
        $output = [];
        $sql = 'SELECT `message`.*,';
        $sql .= ' COALESCE(`user`.`id`, 0) AS `user_id`,';
        $sql .= ' COALESCE(`user`.`is_bot`, 0) AS `is_bot`,';
        $sql .= ' COALESCE(`user`.`first_name`, "") AS `first_name`,';
        $sql .= ' COALESCE(`user`.`last_name`, "") AS `last_name`,';
        $sql .= ' COALESCE(`user`.`username`, "") AS `username`';
        $sql .= ' FROM `default`.`message`';
        $sql .= ' LEFT JOIN `default`.`user` ON `user`.`id` = `message`.`user_id`';
        $sql .= ' WHERE `message`.`chat_id`='.(int) $chat_id;
        $sql .= ' ORDER BY `message`.`id` DESC';
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

    public static function getUserMessages($user_id) {
        $output = [];
        $sql = 'SELECT `message`.*,';
        $sql .= ' COALESCE(`chat`.`type`, "") AS `chat_type`,';
        $sql .= ' COALESCE(`chat`.`title`, "") AS `chat_title`,';
        $sql .= ' COALESCE(`chat`.`first_name`, "") AS `chat_first_name`,';
        $sql .= ' COALESCE(`chat`.`last_name`, "") AS `chat_last_name`';
        $sql .= ' FROM `default`.`message`';
        $sql .= ' LEFT JOIN `default`.`chat` ON `chat`.`id` = `message`.`chat_id`';
        $sql .= ' WHERE `message`.`user_id`='.(int) $user_id;
        $sql .= ' ORDER BY `message`.`chat_id` DESC, `message`.`id` DESC';
        $response = (array) DB::select($sql);
        foreach ($response as $row) {
            $tmp = (array) $row;
            $tmp['text'] = is_null($tmp['text']) ? '' : $tmp['text'];
            $output[] = $tmp;
        }
        return $output;
    }

    public static function checkInput($input) {
        $data = [];
        $checkList = self::columns();
        foreach ($checkList['PK'] as $column) {
            if (empty($input[$column]) || ! is_numeric($input[$column])) {
                return false;
            }
            $data[$column] = $input[$column];
        }
        foreach ($checkList['INT'] as $column) {
            if (isset($input[$column])) {
                $data[$column] = (int) $input[$column];
            }
        }
        foreach ($checkList['TEXT'] as $column) {
            if (isset($input[$column])) {
                $data[$column] = (string) $input[$column];
            }
        }
        foreach ($checkList['TIMESTAMP'] as $column) {
            if (isset($input[$column])) {
                if (is_numeric($input[$column])) {
                    $data[$column] = date('Y-m-d H:i:s', $input[$column]);
                } else {
                    $data[$column] = (string) $input[$column];
                }
            }
        }
        return $data;
    }

    public static function replaceData($input) {
        try {
            $data = self::checkInput($input);
            if ($data == false) {
                return false;
            }
            $exist = DB::select('SELECT * FROM `message` WHERE `id`='.$data['id'].' AND `chat_id`='.$data['chat_id']);
            if (empty($exist)) {
                return self::insertData($data);
            }
            $id = $data['id'];
            $chat_id = $data['chat_id'];
            unset($data['id'], $data['chat_id']);
            $sql = 'UPDATE `message` SET ';
            foreach ($data as $key => $value) {
                $sql .= '`'. $key . "`='".$value."',";
            }
            $sql = substr($sql, 0, -1).' WHERE `id`=? AND `chat_id`=? ';
            // self::logInfo(__METHOD__, 'LINE '.__LINE__.' sql '. var_export($sql, true));
            return DB::update($sql, [$id, $chat_id]);
        } catch (\Exception $e) {
            self::logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR '. var_export($e->getMessage(), true));
            return false;
        }
    }

    public static function insertData($data) {
        $result = DB::table('message')->insert($data);
        self::logInfo(__METHOD__, 'LINE '.__LINE__.' data : ' . var_export($data, true).' result : ' . var_export($result, true));
        return $result;
    }

    public static function updateData($id,$data) {
        $result = DB::table('message')->where('id', $id)->update($data);
        self::logInfo(__METHOD__, 'LINE '.__LINE__.' id : ' . var_export($id, true).' data : ' . var_export($data, true).' result : ' . var_export($result, true));
        return $result;
    }
}
