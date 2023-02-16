<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class CallbackQuery extends Model
{
    use HasFactory;

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
        return DB::table('callback_query')->insert($data);
    }
}
