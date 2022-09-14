<?php

namespace App\Http\Controllers;

use App\Services\InspiringService;
use Illuminate\Http\Request;

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
        return $this->service->inspire();
    }
}
