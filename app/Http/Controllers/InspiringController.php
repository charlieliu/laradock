<?php

namespace App\Http\Controllers;

use App\Services\InspiringService;
// use Illuminate\Http\Request;

class InspiringController extends Controller
{
    public $breadcrumbs = ['Inspiring'=>'/inspire'];
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
        $buttons = ['edit'=>'edit'];
        return view('content_list', [
            'active'        => 'inspire',
            'title'         => 'Inspire List',
            'breadcrumbs'   => $this->breadcrumbs,
            'columns'       => $columns,
            'list'          => $this->parse_list($result, $columns, $buttons)
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
