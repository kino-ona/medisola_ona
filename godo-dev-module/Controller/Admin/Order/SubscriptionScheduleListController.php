<?php

namespace Controller\Admin\Order;

use App;
use Request;

class SubscriptionScheduleListController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->getView()->setDefine("layout", "layout_blank");

        $obj = new \Component\Subscription\Subscription();
        //$db = App::load('DB');

        if (!$uid = Request::get()->get("uid"))
            return $this->js("alert('잘못된 접근입니다.');self.close();");

        $list = $obj->getScheduleListByUid($uid);

        $this->setData("list", $list);
        $this->setData("uid", $uid);
    }
}