<?php


namespace app\Service\Tl;


use app\Service\BaseService;
use Symfony\Component\DomCrawler\Crawler;


class TlService extends BaseService
{
    /**
     * 自动获取外卖单号
     */
    public function qianDao()
    {
        while (true){
            $this->doQianDao();
            sleep(86400);
        }
    }
    public function doQianDao(){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'http://tlhj-us.changyou.com/wxSign/signDaily?_=1647675313600',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => array(
                'Host: tlhj-us.changyou.com',
                'Authorization: eyJhbGciOiJIUzUxMiJ9.eyJKT0lOX0FQUCI6InRsZ2wiLCJKT0lOX1dYIjoid3gwMjViODYyNTQ5ZTFjOTk0IiwiQVVUT19MT0dJTiI6MCwiVCI6MTY0NzY3NTI0NjMyMiwiViI6IjIwMjAxMTExIiwiSVAiOiIxODAxMzYxNjMyNDIiLCJKT0lOX1VTRVIiOiI2MjM1ODc2ZWYzMTUxMjAwMDEyOWE4NjEiLCJBQ1RWIjoic2lnbiJ9.9IYi5lns2YCUPMnXW6w9HM-tDmBWX1ihwh-srWTv_1LeIGBh9HsDFJggX7muDsomNmcttN_Y_nFep1nm-Jac_Q',
                'PLAT: wechat',
                'X-Requested-With: XMLHttpRequest',
                'Accept-Language: zh-cn',
                'APP: tlgl',
                'Accept: application/json, text/javascript, */*; q=0.01',
                'VERSIONCODE: 20201111',
                'ACTIVITY: sign',
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/605.1.15 (KHTML, like Gecko) MicroMessenger/6.8.0(0x16080000) MacWechat/3.3.1(0x13030111) Safari/605.1.15 NetType/WIFI',
                'Referer: http://tlhj-us.changyou.com/tlhj/wxsign/20201123/m/index.shtml',
                'Connection: keep-alive'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $this->writeDo(json_encode($response));
    }

    public function writeDo($txt){
        $myfile = fopen("qiandao.txt", "w");
        fwrite($myfile, $txt);
        fclose($myfile);
    }
}