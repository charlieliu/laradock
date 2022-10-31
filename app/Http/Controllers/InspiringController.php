<?php

namespace App\Http\Controllers;

use App\Services\InspiringService;
// use Illuminate\Http\Request;

class InspiringController extends Controller
{
    private $service;

    public function __construct(InspiringService $inspiringService) {
        $this->service = $inspiringService;
    }

    /**
     * Inspiring List
     * @return string
     */
    public function list() {
        $columns = ['id'=>'ID','author'=>'Author','content'=>'Inspire','operations'=>'Operations'];
        $result = $this->service->list() ?: [];
        $btns = ['edit'=>'edit'];
        return view('content_list', [
            'active'    => 'inspire',
            'title'     => 'Inspire List',
            'columns'   => $columns,
            'list'      => $this->parse_list($result, $columns, $btns)
        ]);
    }

    /**
     * Inspiring detail
     * @return string
     */
    public function inspire() {
        return view('content_p', [
            'active'    => 'inspire',
            'title'     => 'Inspire',
            'content'   => $this->service->inspire()
        ]);
    }
}
