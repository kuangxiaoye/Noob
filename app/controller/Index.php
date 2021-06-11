<?php

namespace app\controller;

use think\facade\Request;

class Index
{
    public function index(){
        $action = Request::param('action');
        if ($action  == 'mark'){
            $service = new \app\Service\Cm\SxService();
            $goodsId = Request::param('goosid');
            $service->attentionGoods($goodsId);
            return "关注成功";
        }

        return "请选择操作";
    }
}