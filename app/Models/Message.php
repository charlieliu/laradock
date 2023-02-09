<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Message extends Model
{
    use HasFactory;

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

    public static function getUserMessages($user_id) {
        $output = [];
        $sql = 'SELECT `message`.*,';
        $sql .= ' COALESCE(`chat`.`type`, "") AS `chat_type`,';
        $sql .= ' COALESCE(`chat`.`title`, "") AS `chat_title`';
        $sql .= ' FROM `default`.`message`';
        $sql .= ' LEFT JOIN `default`.`chat` ON `chat`.`id` = `message`.`chat_id`';
        $sql .= ' WHERE `message`.`user_id`='.(int) $user_id;
        $sql .= ' ORDER BY `message`.`chat_id` ASC, `message`.`id` ASC';
        $response = (array) DB::select($sql);
        foreach ($response as $row) {
            $tmp = (array) $row;
            $tmp['text'] = is_null($tmp['text']) ? '' : $tmp['text'];
            $output[] = $tmp;
        }
        return $output;
    }

    public static function insertData($data) {
        return DB::table('message')->insert($data);
    }

    public static function updateData($id,$data) {
        return DB::table('message')->where('id', $id)->update($data);
    }
}
