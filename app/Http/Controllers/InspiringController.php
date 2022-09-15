<?php

namespace App\Http\Controllers;

use App\Services\InspiringService;
// use Illuminate\Http\Request;

class InspiringController extends Controller
{
    private $service;

    public function __construct(InspiringService $inspiringService)
    {
        $this->service = $inspiringService;
    }

    /**
     * @return string
     */
    public function inspire()
    {
        return view('content_p', [
            'active'    => 'inspire',
            'title'     => 'Inspire',
            'content'   => $this->service->inspire()
        ]);
    }

    public function list()
    {
        $columns = ['id'=>'ID','author'=>'Author','content'=>'Inspire'];
        $list = [];
        $data = $this->service->list() ?: [];
        foreach ($data as $index => $row)
        {
            foreach ($columns as $column => $text)
            {
                $list[$index][$column] = $row->{$column};
            }
        }
        return view('content_list', [
            'active'    => 'inspire',
            'title'     => 'Inspire List',
            'columns'   => $columns,
            'list'      => $list
        ]);
    }
}
