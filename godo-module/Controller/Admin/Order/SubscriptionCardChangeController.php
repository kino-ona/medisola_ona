<?php

namespace Controller\Admin\Order;

use App;
use Request;

class SubscriptionCardChangeController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->getView()->setDefine("layout", "layout_blank");

        if (!$uid = Request::get()->get("uid"))
            return $this->js("alert('잘못된 접근입니다.');self.close();");

        $obj = new \Component\Subscription\Subscription();
        $info = $obj->setUid($uid)
            ->setMode("subscriptionInfo")
            ->get();
        if (!$info)
            return $this->js("alert('신청 정보가 존재하지 않습니다.');self.close();");

        $memNo = $info['memNo'];
        if (!$list = $obj->getCards($memNo))
            return $this->js("alert('등록된 카드가 없습니다.');self.close();");

        $this->setData("uid", $uid);
        $this->setData("idx_card", $info['idx_card']);
        $this->setData('list', $list);
    }
}