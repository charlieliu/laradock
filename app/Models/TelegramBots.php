<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
class TelegramBots extends Model
{
    use HasFactory;

    public static function logInfo($method = '', $info = '') {
        $log = date('Y-m-d H:i:s') . ' ' .$method. ' '.$info;
        Log::debug($log);
    }

    public static function getOne($value=0,$column='id') {
        return DB::table('telegram_bots')->where($column, $value)->first();
    }

    public static function getOneById($id = 0) {
        return self::getOne($id, 'id');
    }

    public static function getOneByUsername($username = '') {
        return self::getOne($username, 'username');
    }

    public static function getOneByUserID($user_id = 0) {
        return self::getOne($user_id, 'user_id');
    }

    public static function getListAll($column='username') {
        $output = [];
        $sql = 'SELECT `telegram_bots`.*,';
        $sql .= ' COALESCE(`user`.`first_name`, "") AS `first_name`,';
        $sql .= ' COALESCE(`user`.`last_name`, "") AS `last_name`';
        $sql .= ' FROM `default`.`telegram_bots`';
        $sql .= ' LEFT JOIN `default`.`user` ON `telegram_bots`.user_id = `user`.id';
        $response = (array) DB::select($sql);
        foreach ($response as $row) {
            $tmp = (array) $row;
            $tmp['last_update_id'] = (int) Cache::get('last_update_id:'.$tmp['username']);
            $output[$tmp[$column]] = $tmp;
        }
        return $output;
    }

    public static function insertData($data) {
        $result = DB::table('telegram_bots')->insert($data);
        self::logInfo(__METHOD__, 'LINE '.__LINE__.' data : ' . var_export($data, true).' result : ' . var_export($result, true));
        return $result;
    }

    public static function updateData($id, $data) {
        $result = DB::table('telegram_bots')->where('id', $id)->update($data);
        self::logInfo(__METHOD__, 'LINE '.__LINE__.' id : ' . var_export($id, true).' data : ' . var_export($data, true).' result : ' . var_export($result, true));
        return $result;
    }
}
