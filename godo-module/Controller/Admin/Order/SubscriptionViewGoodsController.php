<?php

namespace Controller\Admin\Order;

use App;
use Request;

class SubscriptionViewGoodsController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->getView()->setDefine("layout", "layout_blank");
        if (!$uid = Request::get()->get("uid"))
            return $this->js("alert('잘못된 접근입니다.');self.close();");

        $obj = new \Component\Subscription\Subscription();
        $db = App::load('DB');
        $goods = App::load(\Component\Goods\Goods::class);
        $info = $obj->setUid($uid)
            ->setMode("subscriptionInfo")
            ->get();

        if (!$info || !$info['items'])
            return $this->js("alert('주문상품이 존재하지 않습니다.');self.close();");

        $items = $info['items'];
        foreach ($items as $k => $it) {
            $sql = "SELECT delFl FROM " . DB_GOODS . " WHERE goodsNo='{$it['goodsNo']}'";
            $row = $db->fetch($sql);
            if ($row['delFl'] != 'n')
                continue;

            $g = $goods->getGoodsView($it['goodsNo']);
            $images = [];
            if ($imageList = $goods->getGoodsImage($g['goodsNo'], 'list')) {
                foreach ($imageList as $li) {
                    $image = gd_html_goods_image($g['goodsNo'], $li['imageName'], $g['imagePath'], $g['imageStorage'], 40, $g['goodsNm'], '_blank');
                    $images[] = $image;
                }
            }


            $g['images'] = $images;

            $it['selectedOptTxt'] = [];
            if ($selectedOptTxt = json_decode($it['optionText'], true))
                $it['selectedOptTxt'] = $selectedOptTxt;

            $it['goods'] = $g;
            $items[$k] = $it;
        }

        $this->setData("items", $items);
    }
}