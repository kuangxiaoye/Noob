<?php


namespace app\Service\Takeout;


use app\Service\BaseService;
use Symfony\Component\DomCrawler\Crawler;


class BaoWeiService extends BaseService
{
    /**
     * 自动获取外卖单号
     */
    public function autoGetOrder()
    {
        while (true){
            try {
                $cookie = "PHPSESSID=d65b85b53577600be8d47830579fa768; 0a52_we7_wmall_deliveryer_session_1486=eyJpZCI6IjEwOTIxIiwiaGFzaCI6IjAwNTE5YWEwOWY3Y2RjODVhZGFkNGVlY2I4YTcyZDIxIn0%3D";
                $cookie = 'Cookie: ' . $cookie;
                //获取订单列表html数据
                $takeOutList = $this->takeoutList($cookie);
                //做订单截取拆分
                $orderList = $this->handleOrderInfo($takeOutList);
                //做订单按需过滤 todo
                print_r(json_encode($orderList));
                print_r("\n");
                if (count($orderList)>0){
                    foreach ($orderList as $orderId) {
                       $res = $this->confirmOrder($orderId, $cookie);
                        print_r(json_encode($res));
                        print_r("\n");
                    }
                }
            }catch (\Exception $exception){
            }
        }
    }

    public function handleOrderInfo($html)
    {
        $subcrawler = new Crawler($html);
        try {
            $orderInfoList = [];
            $orderList = [];
            $subcrawler->filter('div a')->each(function ($node) use (&$orderInfoList) {
                $orderInfoList[] = $node->attr('href');
            });
        } catch (\Exception $exception) {
        }
        foreach ($orderInfoList as $key => $item) {
            if (strpos($item, 'id') !== false) {
                $orderList[] = substr($item, -7);
            }
        }

        return $orderList;
    }

    public function takeoutList($cookie)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'http://admin.jubaowx.cn/app/index.php?i=1486&c=entry&ctrl=delivery&ac=order&op=takeout&ta=list&do=mobile&m=we7_wmall&status=3',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => array(
                'Connection: keep-alive',
                'Cache-Control: max-age=0',
                'Upgrade-Insecure-Requests: 1',
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Referer: http://admin.jubaowx.cn/app/index.php?i=1486&c=entry&ctrl=delivery&ac=order&op=takeout&ta=list&do=mobile&m=we7_wmall&status=3',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                $cookie,
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function confirmOrder($orderId,$cookie){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://admin.jubaowx.cn/app/index.php?i=1486&c=entry&ctrl=delivery&ac=order&op=takeout&ta=collect&do=mobile&m=we7_wmall&id='.$orderId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => array(
                'authority: admin.jubaowx.cn',
                'cache-control: max-age=0',
                'sec-ch-ua: "Chromium";v="92", " Not A;Brand";v="99", "Google Chrome";v="92"',
                'sec-ch-ua-mobile: ?0',
                'upgrade-insecure-requests: 1',
                'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36',
                'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'sec-fetch-site: none',
                'sec-fetch-mode: navigate',
                'sec-fetch-user: ?1',
                'sec-fetch-dest: document',
                'accept-language: zh-CN,zh;q=0.9,en;q=0.8',
                $cookie
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response,true);
    }
}