<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class UserChat extends Model
{
    use HasFactory;

    public static function logInfo($method = '', $info = '') {
        $log = date('Y-m-d H:i:s') . ' ' .$method. ' '.$info;
        Log::debug($log);
    }

    public static function getOne($user_id = 0, $chat_id = 0) {
        return DB::select('SELECT * FROM `user_chat` WHERE `user_id` = '.$user_id.' AND `chat_id` = '.$chat_id);
    }

    public static function insertData($data) {
        $result = DB::table('user_chat')->insert($data);
        self::logInfo(__METHOD__, 'LINE '.__LINE__.' data : ' . var_export($data, true).' result : ' . var_export($result, true));
        return $result;
    }
}
