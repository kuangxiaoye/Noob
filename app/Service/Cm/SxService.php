<?php


namespace app\Service\Cm;

use app\model\SxdsAccountGoodsList;
use app\Service\Notice\FangTang;
use app\Service\Notice\Wxpusher;
use http\Client\Request;
use League\Flysystem\Cached\Storage\Predis;
use think\Cache;
use think\cache\driver\Redis;
use think\Model;
use GuzzleHttp\Client;

class SxService
{
    /**
     * 售卖统计
     */
    public function statsticInfo()
    {
        $accountListModel = (new SxdsAccountGoodsList());
        $saleInfo = $accountListModel->where("status",2)->whereTime("createon",">=",dateNowDay())->select();

    }

    /**
     * 上传文件
     */
    public function fileUpLoad()
    {
        $token = $this->getTempToken();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://upload.zzaion.com//fileUpload?tempFlag=0&token=$token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('file' => new CURLFILE('/Users/wangye/Downloads/WechatIMG207.png')),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryAYf0aTOhSrtMKLzg'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    /**
     * 获取上传文件的临时token
     */
    public function getTempToken()
    {
        $token = $this->getToken();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://tl.sxds.com/api/comm/imagesTempUploadToken',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "Cookie: nickName=%E7%95%85%E7%8E%A9%E5%B7%A5%E4%BD%9C%E5%AE%A4; actor=; placeOrder=19121671996&3315510010; username=; userId=257022; beforePath=/detail/Z210516210212069574; token=$token"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    /**
     * 神仙代售获取token
     */
    public function getToken()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://tl.sxds.com/api/user/login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'phoneNum=19121671996&password=258765',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $userInfo = json_decode($response, true);

        return $userInfo['data']['token'];
    }

    /**
     * 神仙代售商品发布
     */
    public function doSale()
    {
        //每次发布商品都间隔一分钟
//        sleep(60)

    }


    /**
     * 神仙代售账号爬取通知
     */
    public function sxds()
    {
        while (true) {
            try {
                $this->doCrawSxdsApi();
//                $this->doCrawSxdsApiAll();
            } catch (\Exception $exception) {
            }
            sleep(rand(10, 20));
        }
    }

    /**
     * 定时爬取神仙代售所有账号 每小时执行一次
     */
    public function sxdsgoods()
    {
        while (true) {
            try {
                $this->sxdsRecordAll();
            } catch (\Exception $exception) {
            }
            sleep(3600);
        }
    }

    /**
     * 定时爬取神仙代售所有账号详情
     */
    public function sxdsgoodsdetail()
    {
        while (true) {
            try {
                $this->sxdsStatus();
            } catch (\Exception $exception) {
            }
            sleep(12000);
        }
    }

    public function sxdsme()
    {
        while (true) {
            sleep(rand(5, 10));
            try {
                $this->doPushAccountInfo();
            } catch (\Exception $exception) {
            }
        }
    }

    public function doPushAccountInfo()
    {
        # code...
        $curl = curl_init();
        $address = "https://tl.sxds.com/detail/";
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://tl.sxds.com/wares/?pageSize=12&gameId=74&goodsTypeId=1&jobsId=332&pages=1&areaId=329',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $resu = strstr($response, "goodsListData");
        $resu = substr($resu, strripos($resu, "goodsList:") + 10);

        $goodStr = substr($resu, 0, strrpos($resu, ",goodsShowTileList"));

        $goodListUnSort = explode('goodsSn:"', $goodStr);
        foreach ($goodListUnSort as $item) {
            $goodsId = substr($item, 0, 19);
            if (!strstr($goodsId, "Z")) {
                continue;
            }

            $redis = (new Redis());

            $exs = Cache::get($goodsId);
            if (empty($exs)) {
                //新版 turbo推送 推送给我自己
                (new FangTang())->sendTurbo("震惊！！有新号上架了！！", $address . $goodsId);
                Cache::set($goodsId, $goodsId);
            }
        }
    }

    /***
     * 神仙代售商品列表信息全量同步
     */
    public function sxdsRecordAll()
    {
        $accountListModel = (new SxdsAccountGoodsList());
        $pageSize = 64;
        $pages = 1;
        $totalInfo = $this->getGoodsListApi($pages, $pageSize, 1);
        $pageAll = ceil($totalInfo['total'] / $pageSize); //获取总页数
        do {
            $goodsList = $this->getGoodsListApi($pages, $pageSize, 2);
            sleep(rand(4,6));
            foreach ($goodsList['goodsList'] as $goodsDetail) {
                $priceCurrent = $goodsDetail['price'];
                $goodsId = $goodsDetail['goodsSn'];
                $serverName = $goodsDetail['serverName'];
                $goodsInfo = $accountListModel->where('goodsid', $goodsId)->find();
                if (!empty($goodsInfo)) {
                    $accountListModel::update(['goodsid'=>$goodsId],['price'=>$priceCurrent,'area'=>$serverName,'updateon'=>dateNow()]);
                } else {
                    $accountListModel::create(['goodsid'=>$goodsId,'price'=>$priceCurrent,'price_original' => $priceCurrent,'area'=>$serverName,'createon'=>dateNow()]);
                }
            }

            $pages++;
            $pageAll--;
        } while ($pageAll >= 0);
    }

    public function sxdsStatus()
    {
        $accountListModel = (new SxdsAccountGoodsList());
        $goodsList = $accountListModel->where("status",0)->select()->toArray();
        foreach ($goodsList as $goodsInfo){
            try {

            $goodsId = $goodsInfo['goodsid'];
            $goodsDetail = $this->getSxdsGoodsDetail($goodsId);
            sleep(rand(4,6));
            if (!isset($goodsDetail['data']['showSign'])){
                continue;
            }
            $status = $goodsDetail['data']['showSign'];

            if (!isset($goodsDetail['data']['goodsNum'])){
                continue;
            }
            $finalStatus = $goodsDetail['data']['goodsNum'];

            //售出
            if ($status!==0 and $finalStatus==0){
                $accountListModel::update(['goodsid'=>$goodsId],[
                    'status'=>1,
                    'updateon'=>dateNow(),
                ]);
            }
            //下架
            if ($status!==0 and $finalStatus==1){
                $accountListModel::update(['goodsid'=>$goodsId],[
                    'status'=>2,
                    'updateon'=>dateNow(),
                ]);
            }
            }catch (\Exception $exception){}
        }
    }

    public function getSxdsGoodsDetail($goodsId){
        $curl = curl_init();
        $ip = $this->getProxyRedis();
        $proxyInfo = $this->ipHandle($ip);
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://h5.sxds.com/api/goods/goodsInfo?goodsSn=$goodsId",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_PROXYAUTH => CURLAUTH_BASIC,
            CURLOPT_PROXY => $proxyInfo['ip'],
            CURLOPT_PROXYPORT => $proxyInfo['port'],
            CURLOPT_PROXYUSERPWD => 'w258765:l7pblonu',
            CURLOPT_TIMEOUT => 10,
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response,true);
    }

    public function getPageNumber($originalUrl)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $originalUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        $resu = strstr($response, 'input type="number" autocomplete="off" min="1"');
        $resu = substr($resu, 0, strrpos($resu, 'el-input__inner')); //删除某个字符串之前的字符串
        $resu = explode('max=', $resu)[1];
        $resu = explode('class', $resu);
        $resu = explode('"', $resu[0]);
        $resu = (int)$resu[1];
        curl_close($curl);

        return $resu;
    }


    public function doCrawSxdsApi()
    {
        $accountListModel = (new SxdsAccountGoodsList());
        $goodsList = $this->getGoodsListApi(1,128,2);
        foreach ($goodsList['data']['goodsList'] as $goodsDetail) {
            $title = $goodsDetail['bigTitle'];
            $area = $goodsDetail['areaName'] . "|" . $goodsDetail['serverName'];
            $price = $goodsDetail['price'];
            if (($price>4000 or $price<1500)){
                continue;
            }
            $goodsId = $goodsDetail['goodsSn'];
            $roleLevel = $goodsDetail['roleLevel'];
            $serveName = $goodsDetail['serverName'];
            $address = "http://www.sxds.com/detail/";
            //旧版 http://sc.ftqq.com/?c=wechat&a=bind
            $goodsInfo = $accountListModel->where('goodsid', $goodsId)->find();
            $url = $address . $goodsId;
            $arrayList = ['UID_RBQX96Z7mQ8hDoq5W95a6sdaa1BS'=>['all'],'UID_4ve8SAw4qkbIqR2pWx8tbjZIduuw'=>['all'],'UID_a6ptX5MExMOsqm4EU4PIteo2Hcgv'=>'all'];
            foreach ($arrayList as $array_id=>$areaNeed){

                if (!empty($goodsInfo)) { //更新
                    $priceOriginal = $goodsInfo['price_original'];
                    $priceOld = $goodsInfo['price'];
                    $notice = $goodsInfo['notice'];
                    //差价
                    if ((int)$priceOld != (int)$price or $notice==0) {
                        $gap = $priceOriginal - $price;
//                        if (in_array($serveName,$areaNeed)) {
                            (new Wxpusher())->send('' . "\n 降价$gap" . "\n 现价 $price" . "\n $area" . "\n $title.$roleLevel", 'url', true, $array_id,$url);
                            $accountListModel::update(['price'=>$price,'notice'=>1,'updateon'=>dateNow()],['goodsid'=>$goodsId]);
//                        }
                    }
                } else { //新增
//                    if (in_array($serveName,$areaNeed)) {
                        (new Wxpusher())->send('' . "\n 新号 价格$price" . "\n $area" . "\n $title.$roleLevel", 'url', true, $array_id,$url);
                        $accountListModel::create(['goodsid'=>$goodsId,'price'=>$price,'price_original' => $price,'notice'=>1,'createon'=>dateNow()]);
//                    }
                }
            }
        }
    }

    public
    function getGoodsListApiold($pages, $pageSize, $isTotal)
    {
        $curl = curl_init();
        //要计算total,只发送一个页数
        if ($isTotal == 1) {
            $url = "https://h5.sxds.com/api/goods/getGoodsList?keyWord=&gameId=74&pages=1&pageSize=1&goodsTypeId=1";
        } else if ($isTotal == 2) {// 要获取所有数据
            $url = "https://h5.sxds.com/api/goods/getGoodsList?keyWord=&gameId=74&pages=$pages&pageSize=$pageSize&goodsTypeId=1";
        } else {
            $url = "https://h5.sxds.com/api/goods/getGoodsList?keyWord=&gameId=74&pages=$pages&pageSize=$pageSize&goodsTypeId=1";
        }
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_PROXYAUTH => CURLAUTH_BASIC,
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response, true);
    }

    public function getGoodsListApi($pages, $pageSize, $isTotal){
        //要计算total,只发送一个页数
        if ($isTotal == 1) {
            $url = "https://h5.sxds.com/api/goods/getGoodsList?keyWord=&gameId=74&pages=1&pageSize=1&goodsTypeId=1";
        } else if ($isTotal == 2) {// 要获取所有数据
            $url = "https://h5.sxds.com/api/goods/getGoodsList?keyWord=&gameId=74&pages=$pages&pageSize=$pageSize&goodsTypeId=1";
        } else {
            $url = "https://h5.sxds.com/api/goods/getGoodsList?keyWord=&gameId=74&pages=$pages&pageSize=$pageSize&goodsTypeId=1";
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://h5.sxds.com/api/goods/getGoodsList?keyWord=&gameId=74&pages=1&pageSize=40&goodsTypeId=1',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => array(
                'Connection: keep-alive',
                'timeStamp: 1641819828',
                'visitauth: Ze3Ib9fQ7PP1A7Wt0P9TVievJaQ1EBXh4vUspQuT',
                'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1',
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: */*',
                'Sec-Fetch-Site: same-origin',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Dest: empty',
                'Referer: https://h5.sxds.com/',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Cookie: Hm_lvt_bd4baaba449154e8192d79a115ae9ac3=1641126297,1641197335,1641442891,1641463058; Hm_lpvt_bd4baaba449154e8192d79a115ae9ac3=1641519933'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response, true);
    }

    /***
     * 爬取神仙代售账号web端
     */
    public function doCrawSxds()
    {
        $accountListModel = (new SxdsAccountGoodsList());
        $client = new Client();
        $address = "http://tl.sxds.com/detail/";
        $res = $client->post('https://www.sxds.com/wares/?pageSize=24&gameId=74&goodsTypeId=1&pages=1&areaId=329&serverId=8610');
        $body = $res->getBody();
        $response = $body->getContents();

        $resu = strstr($response, "goodsListData");
        $resu = substr($resu, strripos($resu, "goodsList:") + 10);

        $goodStr = substr($resu, 0, strrpos($resu, ",goodsShowTileList"));

        $goodListUnSort = explode('goodsSn:"', $goodStr);
        $infoList = [];
        foreach ($goodListUnSort as $item) {
            try {
                $goodsId = substr($item, 0, 19);
                if (!strstr($goodsId, "Z")) {
                    continue;
                }

                $priceNew = substr($item, strpos($item, "price:"), "60");
                $priceNew = substr($priceNew, 0, strpos($priceNew, "provideCardId"));
                $priceNew = explode(",", explode('price:"', $priceNew)[1])[0];
                $priceNew = (int)substr($priceNew, 0, strrpos($priceNew, '"'));


                //旧版 http://sc.ftqq.com/?c=wechat&a=bind
                $goodsInfo = $accountListModel->where('goodsid', $goodsId)->find();
                $url = $address . $goodsId;
                $array_id = ['UID_RBQX96Z7mQ8hDoq5W95a6sdaa1BS', 'UID_4ve8SAw4qkbIqR2pWx8tbjZIduuw'];
                if (!empty($goodsInfo)) {
                    $priceOld = $goodsInfo['price'];
                    //差价
                    if ($priceOld > $priceNew) {
                        $gap = $priceOld - $priceNew;
                        (new Wxpusher())->send($url . "\n 降价$gap" . "\n 现价 $priceNew", 'url', true, $array_id);
                    }
                } else {
                    (new Wxpusher())->send($url . "\n 新号 价格$priceNew", 'url', true, $array_id);
                }

                //降价新增都更新
                $infoList[] = [
                    'goodsid' => $goodsId,
                    'price' => $priceNew,
                ];
                $accountListModel->replace()->saveAll($infoList);
            } catch (\Exception $exception) {

            }
        }
    }


    public function reviseGoodsArea(){
        $nullPrice  = (new SxdsAccountGoodsList())->where('status',1)->select()->toArray();
        foreach ($nullPrice as $goodsInfo){
            $goodsId = $goodsInfo['goodsid'];
            $goodsDetail = $this->getSxdsGoodsDetail($goodsId);
            $serverName = $goodsDetail['data']['serverName'];
            (new SxdsAccountGoodsList())::update(['goodsid'=>$goodsId],['area'=>$serverName]);
        }
    }


    public function ipHandle($ip)
    {
        $ipInfo['ip'] = explode(":", $ip)[0];
        $ipInfo['port'] = explode(":", $ip)[1];

        return $ipInfo;
    }

    public function getKDLip($orderId = 902735621238332)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://kps.kdlapi.com/api/getkps/?orderid=' . $orderId . '&num=1&pt=1&format=json&sep=1',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response, true);
        return $response['data']['proxy_list'][0];
    }

    public function getProxyRedis()
    {
        $ip = \think\facade\Cache::get("proxy");
        if (!empty($ip)) {
            return $ip;
        } else {
            $ip = $this->getKDLip();
            \think\facade\Cache::set("proxy", $ip, 3600);
            return $ip;
        }
    }
}