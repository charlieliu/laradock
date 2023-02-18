<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class ConfigCoinNetwork extends Model
{
    use HasFactory;

    public static function logInfo($method = '', $info = '') {
        $log = date('Y-m-d H:i:s') . ' ' .$method. ' '.$info;
        Log::debug($log);
    }

    public static function getListAll($condition=[]) {
        $table = DB::table('config_coin_network')
            ->join('config_coin', 'config_coin.id', '=', 'config_coin_network.coin_id')
            ->join('config_network', 'config_network.id', '=', 'config_coin_network.network_id')
            ->select(
                'config_coin_network.*',
                'config_coin.symbol AS coin_symbol',
                'config_coin.zh_name AS coin_zh_name',
                'config_coin.en_name AS coin_en_name',
                'config_coin.is_open AS coin_open',
                'config_coin.link AS coin_link',
                'config_network.symbol AS network_symbol',
                'config_network.zh_name AS network_zh_name',
                'config_network.en_name AS network_en_name',
                'config_network.is_open AS network_open',
                'config_network.link AS network_link'
            );
        foreach ($condition as $key => $value) {
            $table->where($key, '=', $value);
        }
        return $table->get();
    }

    public static function getCoinList($condition=[]) {
        $table = DB::table('config_coin');
        foreach ($condition as $key => $value) {
            $table->where($key, '=', $value);
        }
        return $table->get();
    }

    public static function getNetworkList($condition=[]) {
        $table = DB::table('config_network');
        foreach ($condition as $key => $value) {
            $table->where($key, '=', $value);
        }
        return $table->get();
    }

    public static function insertCoin($data) {
        return DB::table('config_coin')->insert($data);
    }

    public static function updateCoin($id, $data) {
        return DB::table('config_coin')->where('id', $id)->update($data);
    }

    public static function insertNetwork($data) {
        return DB::table('config_network')->insert($data);
    }

    public static function updateNetwork($id, $data) {
        return DB::table('config_network')->where('id', $id)->update($data);
    }

    public static function insertCoinNetwork($data) {
        return DB::table('config_coin_network')->insert($data);
    }

    public static function updateCoinNetwork($id, $data) {
        return DB::table('config_coin_network')->where('id', $id)->update($data);
    }
}
