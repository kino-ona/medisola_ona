<?php

namespace Controller\Front\Subscription;

use App;
use Request; 

class SubscriptionBenefitController extends \Controller\Front\Controller 
{
    public function index()
    {
        if (!$goodsNo = Request::get()->get("goodsNo"))
            return $this->js("alert('잘못된 접근입니다.');");

        $getValue = Request::get()->toArray();

        $db = App::load('DB');
        $obj = App::load(\Component\Subscription\Subscription::class);
        $goods = App::load(\Component\Goods\Goods::class);

        if (!$obj->isSubscriptionGoods($goodsNo))
            return $this->js("alert('정기배송 상품이 아닙니다.');");
        
        if (!$row = $db->fetch("SELECT goodsPrice FROM " . DB_GOODS . " WHERE goodsNo='{$goodsNo}'"))
            return $this->js("alert('상품이 존재하지 않습니다,');");
       
        $goodsPrice = $row['goodsPrice'];

        $subCfg = $obj->setGoods($goodsNo)
                            ->setMode("getGoodsCfg")
                            ->setPrice($goodsPrice)
                            ->get();


       $this->setData('goodsNo', $goodsNo);
       $this->setData("goodsPrice", $goodsPrice);
       $this->setData($subCfg);

    }
}