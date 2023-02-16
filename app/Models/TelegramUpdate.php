<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
class TelegramUpdate extends Model
{
    use HasFactory;

    public static function logInfo($method = '', $info = '') {
        $log = date('Y-m-d H:i:s') . ' ' .$method. ' '.$info;
        Log::debug($log);
    }

    public static function insertData($input, $user_id=0) {
        try {
            if (empty($input['update_id'])) {
                return false;
            }
            $data['id'] = (int) $input['update_id'];
            foreach (['chat_id','message_id','edited_message_id','callback_query_id','my_chat_member_updated_id','chat_member_updated_id'] as $column) {
                if ( ! empty($input[$column])) {
                    $data[$column] = (int) $input[$column];
                }
            }
            $table = Schema::hasTable('telegram_update_'.$user_id) ? 'telegram_update_'.$user_id : 'telegram_update';
            self::logInfo(__METHOD__, 'LINE '.__LINE__.' table : ' . var_export($table, true));
            self::logInfo(__METHOD__, 'LINE '.__LINE__.' data : ' . var_export($data, true));
            return DB::table($table)->insert($data);
        } catch (\Exception $e) {
            self::logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR '. var_export($e->getMessage(), true));
            return false;
        }
    }
}
