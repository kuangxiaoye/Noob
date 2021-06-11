<?php

namespace app\controller;

use think\facade\Request;

class Index
{
    public function index(){
        $action = Request::param('action');
        if (empty($action)){
            return "请选择操作";
        }
        if ($action  == 'mark'){
            $service = new \app\Service\Cm\SxService();
            $goodsId = Request::param('goosid');
            if (empty($goodsId)){
                return "请输入商品号";
            }
            $service->attentionGoods($goodsId);
            return "关注成功";
        }

        return  "干嘛！";
    }
}