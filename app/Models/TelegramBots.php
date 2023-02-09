<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
class TelegramBots extends Model
{
    use HasFactory;

    public static function getOneById($id = 0) {
        return DB::table('telegram_bots')->where('id', $id)->first();
    }

    public static function getOneByUsername($username = '') {
        return DB::table('telegram_bots')->where('username', $username)->first();
    }

    public static function getListAll() {
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
            $tmp['last_update_id'] = (int) Cache::get('last_update_id:'.$tmp['user_id']);
            $output[$tmp['username']] = $tmp;
        }
        return $output;
    }

    public static function insertData($data) {
        return DB::table('telegram_bots')->insert($data);
    }

    public static function updateData($id,$data) {
        return DB::table('telegram_bots')->where('id', $id)->update($data);
    }
}
