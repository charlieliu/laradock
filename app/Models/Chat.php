<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class Chat extends Model
{
    use HasFactory;

    public static function logInfo($method = '', $info = '') {
        $log = date('Y-m-d H:i:s') . ' ' .$method. ' '.$info;
        Log::debug($log);
    }

    public static function getOne($id = 0) {
        return DB::table('chat')->where('id', $id)->first();
    }

    public static function getListAll() {
        $output = [];
        $userIDs = [];
        $response = (array) DB::select('SELECT * FROM `chat`');
        foreach ($response as $row) {
            $tmp = (array) $row;
            if (is_null($tmp['title'])) {
                $tmp['title'] =  '';
                $userIDs[] = $tmp['id'];
            }
            $output[$tmp['id']] = $tmp;
        }
        if ( ! empty($userIDs)) {
            $response = (array) DB::select('SELECT * FROM `user` WHERE id IN('.implode(',',$userIDs).')');
            foreach ($response as $row) {
                $tmp = (array) $row;
                $title = '';
                if ( ! empty($tmp['first_name']) ||  ! empty($tmp['last_name'])) {
                    $title = $tmp['first_name'].' '.$tmp['last_name'];
                }
                if ( ! empty($tmp['username'])) {
                    $title = $tmp['username'];
                }
                if ( ! empty($title)) {
                    $output[$tmp['id']]['title'] = $title;
                }
            }
        }
        return $output;
    }

    public static function insertData($data) {
        $result = DB::table('chat')->insert($data);
        self::logInfo(__METHOD__, 'LINE '.__LINE__.' data : ' . var_export($data, true).' result : ' . var_export($result, true));
        return $result;
    }

    public static function updateData($id,$data) {
        $result = DB::table('chat')->where('id', $id)->update($data);
        self::logInfo(__METHOD__, 'LINE '.__LINE__.' id : ' . var_export($id, true).' data : ' . var_export($data, true).' result : ' . var_export($result, true));
        return $result;
    }
}
