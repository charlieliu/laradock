<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class CallbackQuery extends Model
{
    use HasFactory;

    public static function logInfo($method = '', $info = '') {
        $log = date('Y-m-d H:i:s') . ' ' .$method. ' '.$info;
        Log::debug($log);
    }

    public static function getOne($id = 0) {
        return DB::table('callback_query')->where('id', $id)->first();
    }

    public static function insertData($input) {
        $data = [];
        foreach (['id','user_id','chat_id','message_id','chat_instance','data','game_short_name'] as $column) {
            if (isset($input[$column])) {
                $data[$column] = $input[$column];
            }
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $result = DB::table('callback_query')->insert($data);
        self::logInfo(__METHOD__, 'LINE '.__LINE__.' data : ' . var_export($data, true).' result : ' . var_export($result, true));
        return $result;
    }
}
