<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class User extends Model
{
    use HasFactory;


    public static function getOne($id = 0) {
        return DB::table('user')->where('id', $id)->first();
    }

    public static function getListAll() {
        $output = [];
        $response = (array) DB::select('SELECT * FROM `user`');
        foreach ($response as $row) {
            $tmp = [
                'id'            => '',
                'is_bot'        => false,
                'first_name'    => '',
                'last_name'     => '',
                'username'      => '',
                'language_code' => ''
            ];
            $row = (array) $row;
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
        return DB::table('user')->insert($data);
    }

    public static function updateData($id,$data) {
        return DB::table('user')->where('id', $id)->update($data);
    }
}
