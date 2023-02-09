<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class UserChat extends Model
{
    use HasFactory;

    public static function getOne($user_id = 0, $chat_id = 0) {
        return DB::select('SELECT * FROM `user_chat` WHERE `user_id` = '.$user_id.' AND `chat_id` = '.$chat_id);
    }

    public static function insertData($data) {
        return DB::table('user_chat')->insert($data);
    }
}
