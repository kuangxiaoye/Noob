<?php

namespace app\controller;

use app\Service\Cm\SxService;
use app\Service\Takeout\BaoWeiService;
use think\facade\Request;

class Index
{
    public function index()
    {
        $service = new SxService();
        $service->reviseGoodsArea();
    }
}