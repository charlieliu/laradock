<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class User extends Model
{
    use HasFactory;

    public static function logInfo($method = '', $info = '') {
        $log = date('Y-m-d H:i:s') . ' ' .$method. ' '.$info;
        Log::debug($log);
    }

    public static function getOne($id = 0) {
        return DB::table('user')->where('id', $id)->first();
    }

    public static function getListAll() {
        $output = [];
        $response = (array) DB::select('SELECT * FROM `user`');
        foreach ($response as $row) {
            $tmp = [
                'id'            => 0, // DB column BIGINT
                'is_bot'        => 0, // DB column TINYINT(1)
                'first_name'    => '',
                'last_name'     => '',
                'username'      => '',
                'language_code' => ''
            ];
            $row = (array) $row;
            $row['is_bot'] = (int) $row['is_bot'];
            foreach ($row as $key => $value) {
                if ( ! is_null($value) || is_bool($value)) {
                    $tmp[$key] = $value;
                }
            }
            $tmp['bot'] = $row['is_bot'] === 1 ? 'Yes' : 'No';
            $output[$tmp['id']] = $tmp;
        }
        return $output;
    }

    public static function insertData($data) {
        $result = DB::table('user')->insert($data);
        self::logInfo(__METHOD__, 'LINE '.__LINE__.' data : ' . var_export($data, true).' result : ' . var_export($result, true));
        return $result;
    }

    public static function updateData($id, $data) {
        $result = DB::table('user')->where('id', $id)->update($data);
        self::logInfo(__METHOD__, 'LINE '.__LINE__.' id : ' . var_export($id, true).' data : ' . var_export($data, true).' result : ' . var_export($result, true));
        return $result;
    }
}
