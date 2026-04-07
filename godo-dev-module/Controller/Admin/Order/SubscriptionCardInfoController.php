<?php

namespace Controller\Admin\Order;

use App;
use Request;

class SubscriptionCardInfoController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->getView()->setDefine("layout", "layout_blank");

        $obj = new \Component\Subscription\Subscription();
        if (!$idx = Request::get()->get("idx"))
            return $this->js("alert('잘못된 접근입니다.');self.close();");

        if (!$info = $obj->setCard($idx)->get())
            return $this->js("alert('카드정보가 존재하지 않습니다.');self.close();");

        $payKey = $obj->setCard($idx)
            ->setMode("getPayKey")
            ->get();
        $info['payKey'] = $payKey;
        $this->setData($info);
    }
}