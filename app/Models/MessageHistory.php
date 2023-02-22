<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageHistory extends Model
{
    use HasFactory;

    public static function logInfo($method = '', $info = '') {
        $log = date('Y-m-d H:i:s') . ' ' .$method. ' '.$info;
        Log::debug($log);
    }

    public static function getListAll($value=0, $column='message_id') {
        $output = [];
        $sql = 'SELECT `message_histories`.*,';
        $sql .= ' COALESCE(`user`.`is_bot`, 0) AS `is_bot`,';
        $sql .= ' COALESCE(`user`.`first_name`, "") AS `first_name`,';
        $sql .= ' COALESCE(`user`.`last_name`, "") AS `last_name`,';
        $sql .= ' COALESCE(`user`.`username`, "") AS `username`';
        $sql .= ' FROM `default`.`message_histories`';
        $sql .= ' LEFT JOIN `default`.`user` ON `user`.`id` = `message`.`user_id`';
        $sql .= ' WHERE `message_histories`.`'.$column.'`='.(int) $value;
        $sql .= ' ORDER BY `message_histories`.`id` DESC';
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

    public static function getOne($value = 0, $column='id') {
        return DB::table('message_histories')->where($column, $value)->first();
    }

    public static function insertData($data) {
        $result = DB::table('message_histories')->insert($data);
        self::logInfo(__METHOD__, 'LINE '.__LINE__.' data : ' . var_export($data, true).' result : ' . var_export($result, true));
        return $result;
    }

    public static function updateData($id,$data) {
        $result = DB::table('message_histories')->where('id', $id)->update($data);
        self::logInfo(__METHOD__, 'LINE '.__LINE__.' id : ' . var_export($id, true).' data : ' . var_export($data, true).' result : ' . var_export($result, true));
        return $result;
    }
}
