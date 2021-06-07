<?php


namespace app\Service\Cm;


use app\model\DdCurrencyGoodsList;
use app\Service\BaseService;
use Symfony\Component\DomCrawler\Crawler;


class DdService extends BaseService
{
    /**
     * 落地天龙DD价格数据 每分钟同步一次
     * @throws \Exception
     */
    public function doSyncTl()
    {
        $gameCode = 'u7udm8';
        $goodsType = '1xv82k';
        $game = '天龙八部怀旧';
        $this->ddGoodsSync($gameCode, $goodsType, $game);
    }

    /**
     * 根据游戏与类型同步数据到数据库
     * @param $gameCode
     * @param $goodsType
     * @param $game
     * @throws \Exception
     */
    public function ddGoodsSync($gameCode, $goodsType, $game)
    {

        $page = 1;
        $goodInfoList = [];
        $goodsListAll = [];
        $goodsListByArea = [];
        $goodsListNeed = [];
        $goodsModel = new DdCurrencyGoodsList();
        do {
            try {
                $goodInfoList = $this->getGoodsIdList($gameCode, $goodsType, $page);
            } catch (\Exception $exception) {
            }
            $page++;
            $goodsListAll = array_merge($goodsListAll, $goodInfoList);
        } while (!empty($goodInfoList));

        foreach ($goodsListAll as $item) {
            $goodsListByArea[$item['area']][] = $item;
        }

        foreach ($goodsListByArea as $area => $infoList) {
            if (count($infoList) <= 1) {
                continue;
            }
            array_multisort(array_column($infoList, 'ratio'), SORT_DESC, $infoList);
            $infoList[0]['area'] = $area;
            $infoList[0]['game'] = $game;
            $goodsListNeed[] = $infoList[0];
        }

        $goodsModel->replace()->saveAll($goodsListNeed);
    }

    /**
     * 获取Dd商品列表
     * @param     $gameCode
     * @param     $goodsType
     * @param     $page
     * @param int $minPrice
     * @param int $maxPrice
     */

    public function getGoodsIdList($gameCode, $goodsType, $page)
    {
        $html = $this->getGoodsIdListPage($gameCode, $goodsType, $page, $minPrice = 100, $maxPrice = 200);
        $goodsInfoList = [];
        $subcrawler = new Crawler($html);
        try {

        $subcrawler->filter('.goods-list-item')->each(function ($node) use (&$goodsInfoList) {
            $title = trim($node->filter('.game-account-flag')->text());
            $game = trim($node->filter('.game-qufu-value a')->eq(0)->text());
            $area = trim($node->filter('.game-qufu-value a')->eq(2)->text());
            $goodsUrl = $node->filter('.h1-box h2 a')->attr('href');
            $goodsId = substr(substr($goodsUrl, 10), 0, 20); //做字符串截取
            $stock = $node->filter('.kucun span')->text();
            $priceStr = $node->filter('.goods-price span')->text();
            $price = substr(substr($priceStr, 3), 0, 3);
            $ratioStr = $node->filter('.p-r66 p')->eq(0)->text();
            $ratio = preg_replace('/([\x80-\xff]*)/i', '', substr($ratioStr, 5));
            $goodsInfoList[] = [
                'goodsid'  => $goodsId,
                'game'     => $game,
                'ratio'    => $ratio,
                'stock'    => $stock,
                'title'    => $title,
                'price'    => $price,
                'area'     => $area,
                'createon' => dateNow(),
            ];
            print_r($goodsInfoList);
        });

        }catch (\Exception $exception){
            print_r($exception);
        }
        return $goodsInfoList;
    }

    private function getGoodsIdListPage($gameCode, $goodsType, $page, $minPrice = 100, $maxPrice = 200)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://www.dd373.com/s-$gameCode-0-0-0-0-0-$goodsType-0-2-0-0-0-$page-0-5-0.html?MinPrice=$minPrice&MaxPrice=$maxPrice",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => array(),
        ));

        $html = curl_exec($curl);

        curl_close($curl);

        return $html;
    }
}