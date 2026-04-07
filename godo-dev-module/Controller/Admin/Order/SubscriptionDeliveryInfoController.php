<?php

namespace Controller\Admin\Order;

use App;
use Request;

class SubscriptionDeliveryInfoController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->getView()->setDefine("layout", "layout_blank");

        if (!$uid = Request::get()->get("uid"))
            return $this->js("alert('잘못된 접근입니다.');self.close();");

        $obj = new \Component\Subscription\Subscription();
        if (!$info = $obj->setUid($uid)->setMode("subscriptionInfo")->get())
            return $this->js("alert('배송정보가 존재하지 않습니다.');self.close();");

        $info['orderPhone'] = explode('-', $info['orderPhone']);
        $info['orderCellPhone'] = explode('-', $info['orderCellPhone']);

        $info['receiverPhone'] = explode('-', $info['receiverPhone']);
        $info['receiverCellPhone'] = explode('-', $info['receiverCellPhone']);

        $this->setData($info);
        $this->setData("uid", $uid);
    }
}