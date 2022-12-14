<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function parse_list($input, $columns, $btns = [])
    {
        $output = [];
        foreach ($input as $index => $row)
        {
            $row = (array) $row;
            foreach ($columns as $column => $text)
            {
                if ($column=='operations') {
                    foreach ($btns as $btn_key => $btn_col) {
                        if ($btn_key == 'tg_detail' && isset($row[$btn_col])) {
                            $output[$index]['operations'][$btn_key] = '/tb/read/'.$row[$btn_col];
                        }
                        if ($btn_key == 'tg_run' && isset($row[$btn_col])) {
                            $output[$index]['operations'][$btn_key] = '/tb/run/'.$row[$btn_col];
                        }
                        if ($btn_key == 'tg_chat_messages' && isset($row[$btn_col])) {
                            $output[$index]['operations'][$btn_key] = '/tb/chat_messages/'.$row[$btn_col];
                        }
                        if ($btn_key == 'tg_user_messages' && isset($row[$btn_col])) {
                            $output[$index]['operations'][$btn_key] = '/tb/user_messages/'.$row[$btn_col];
                        }
                        if ($btn_key == 'edit') {
                            $output[$index]['operations'][$btn_key] = 'edit';
                        }
                    }
                } else {
                    if ( ! isset($row[$column])) {
                        echo 'METHOD : '.__METHOD__.' / LINE : '.__LINE__.' / column : '.json_encode($column).PHP_EOL;
                        echo 'METHOD : '.__METHOD__.' / LINE : '.__LINE__.' / row : '.json_encode($row).PHP_EOL;
                        exit;
                    }
                    $output[$index][$column] = $row[$column];
                }
            }
        }
        return $output;
    }
}
