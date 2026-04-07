<?php

namespace Controller\Admin\Goods;

use App;
use Request;

class SubscriptionGoodsSetController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->getView()->setDefine("layout", "layout_blank");
        
        if (!$goodsNo = Request::get()->get("goodsNo"))
            return $this->js("alert('잘못된 접근입니다.');self.close();");

        $obj = new \Component\Subscription\Subscription();
        $cfg = $obj->getGoodsCfg($goodsNo);
        $this->setData($cfg);
        $this->setData("goodsNo", $goodsNo);
    }
}