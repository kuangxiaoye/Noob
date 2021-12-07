<?php

namespace app\controller;

use think\Request;

class Rss
{
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index()
    {
        $url =  $this->request->param('url');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: SID_navi=001002'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $res = simplexml_load_string($response);
        foreach ($res->channel->item as $key =>$item){
            $item->guid=md5($item->title);
        }
        $return =$res->asXML();;
        print_r($return);
    }
}