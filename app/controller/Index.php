<?php

namespace app\controller;

use think\facade\Request;

class Index
{
    public function index(){
            $service = new \app\Service\Cm\SxService();
            $service->reviseOriginalPrice();
            return "结束";
    }
}