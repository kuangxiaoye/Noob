<?php


namespace app\Service\Cm;

use app\model\SxdsAccountGoodsList;
use app\Service\Notice\FangTang;
use app\Service\Notice\Wxpusher;
use League\Flysystem\Cached\Storage\Predis;
use think\Cache;
use think\cache\driver\Redis;

class SxService
{
    /**
     * 神仙代售上架商品
     */
    public function toSale()
    {

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
                $this->doCrawSxds();
            } catch (\Exception $exception) {
            }
            sleep(rand(5, 10));
        }
    }

    /**
     * 定时爬取神仙代售所有账号
     */
    public function sxdsgoods()
    {
        while (true) {
            try {
                $this->sxdsPriceChangeRecord();
            } catch (\Exception $exception) {
            }
            sleep(1200);
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
    public function sxdsPriceChangeRecord()
    {
        $ddAccountGoodsModel = (new SxdsAccountGoodsList());
        $originalUrl = 'https://tl.sxds.com/wares/?pageSize=12&gameId=74&goodsTypeId=1';
        $end = $this->getPageNumber($originalUrl);
        $start = 1;
        do {
            sleep(rand(0, 2));
            $url = 'https://tl.sxds.com/wares/?pageSize=12&gameId=74&goodsTypeId=1&pages=' . $start . '';
            $infoList = [];
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
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $resu = strstr($response, "goodsListData");
            $resu = substr($resu, strripos($resu, "goodsList:") + 10);

            $goodStr = substr($resu, 0, strrpos($resu, ",goodsShowTileList"));

            $goodListUnSort = explode('goodsSn:"', $goodStr);

            foreach ($goodListUnSort as $item) {
                try {
                    $goodsId = substr($item, 0, 19);
                    if (!strstr($goodsId, "Z")) {
                        continue;
                    }
                    $price = substr($item, strpos($item, "price:"), "30");
                    $price = substr($price, 0, strpos($price, "provideCardId"));
                    $price = explode(",", explode('price:"', $price)[1])[0];
                    $price = (int)substr($price, 0, strrpos($price, '"'));


                    $infoList[] = [
                        'goodsid' => $goodsId,
                        'price' => $price,
                    ];
                } catch (\Exception $exception) {
                }
            }
            $ddAccountGoodsModel->replace()->saveAll($infoList);
            $start += 1;
        } while ($start < $end);
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

    /***
     * 爬取神仙代售账号
     */
    public function doCrawSxds()
    {
        $accountListModel = new SxdsAccountGoodsList();
        $curl = curl_init();
        $address = "http://tl.sxds.com/detail/";
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://tl.sxds.com/wares/?pageSize=24&gameId=74&goodsTypeId=1&pages=1',
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
        $infoList = [];
        foreach ($goodListUnSort as $item) {
            try {
                $goodsId = substr($item, 0, 19);
                if (!strstr($goodsId, "Z")) {
                    continue;
                }
                $priceNew = substr($item, strpos($item, "price:"), "30");
                $priceNew = substr($priceNew, 0, strpos($priceNew, "provideCardId"));
                $priceNew = explode(",", explode('price:"', $priceNew)[1])[0];
                $priceNew = (int)substr($priceNew, 0, strrpos($priceNew, '"'));

//            $redis = (new Redis());
//            $exs = $redis->get($goodsId);
//            if (empty($exs)) {
                //旧版 http://sc.ftqq.com/?c=wechat&a=bind
                $goodsInfo = $accountListModel->where('goodsid', $goodsId)->find();
                $url = $address . $goodsId;
                $priceOld = $goodsInfo['price'];
                //差价
                if ($priceOld > $priceNew and !empty($priceNew)) {
                    $gap = $priceOld - $priceNew;
                    (new Wxpusher())->send($url . "\n 降价$gap", 'url', true, 'UID_RBQX96Z7mQ8hDoq5W95a6sdaa1BS');
                }
                //新上架
                if (empty($goodsInfo)) {
                    $infoList[] = [
                        'goodsid' => $goodsId,
                        'price' => $priceNew,
                    ];
                    (new Wxpusher())->send($url, 'url', true, 'UID_RBQX96Z7mQ8hDoq5W95a6sdaa1BS');
                }
//                $redis->set($goodsId, $goodsId);
//            }
            } catch (\Exception $exception) {

            }
        }
        $accountListModel->replace()->saveAll($infoList);
    }
}