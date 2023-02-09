<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Chat extends Model
{
    use HasFactory;

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
        return DB::table('chat')->insert($data);
    }

    public static function updateData($id,$data) {
        return DB::table('chat')->where('id', $id)->update($data);
    }
}
