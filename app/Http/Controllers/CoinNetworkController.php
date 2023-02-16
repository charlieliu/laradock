<?php

namespace App\Http\Controllers;

use App\Models\ConfigCoinNetwork;

class CoinNetworkController extends Controller
{
    public $breadcrumbs = [];
    private $model;

    public function __construct() {
        $this->model = new ConfigCoinNetwork();
    }

    /**
     * @return string
     */
    public function coin() {

        $result = $this->model::getCoinList();
        $buttons = ['edit'=>'edit'];
        $columns = [
            'id'        => 'ID',
            'symbol'    => 'Symbol',
            'zh_name'   => 'Chinese Name',
            'en_name'   => 'English Name',
            'link'      => 'Link',
            'operations'=>' Operations'
        ];
        return view('content_list', [
            'active'        => 'coin',
            'title'         => 'Coin List',
            'breadcrumbs'   => ['Coin'=>'/coin'],
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns, $buttons)
        ]);
    }

    public function network() {

        $result = $this->model::getNetworkList();
        $buttons = ['edit'=>'edit'];
        $columns = [
            'id'        => 'ID',
            'symbol'    => 'Symbol',
            'zh_name'   => 'Chinese Name',
            'en_name'   => 'English Name',
            'link'      => 'Link',
            'operations'=> 'Operations'
        ];
        return view('content_list', [
            'active'        => 'network',
            'title'         => 'Network List',
            'breadcrumbs'   => ['Network'=>'/network'],
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns, $buttons)
        ]);
    }

    public function list() {

        $result = $this->model::getListAll();
        $buttons = ['edit'=>'edit'];
        $columns = [
            'id'                => 'ID',
            'coin_symbol'       => 'Coin Symbol',
            'coin_zh_name'      => 'Coin Chinese Name',
            'coin_en_name'      => 'Coin English Name',
            'network_symbol'    => 'Network Symbol',
            'network_zh_name'   => 'Network Chinese Name',
            'network_en_name'   => 'Network English Name',
            'operations'        => 'Operations'
        ];
        return view('content_list', [
            'active'        => 'coin_network',
            'title'         => 'Coin Network List',
            'breadcrumbs'   => ['Coin Network'=>'/coin_network'],
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns, $buttons)
        ]);
    }
}
