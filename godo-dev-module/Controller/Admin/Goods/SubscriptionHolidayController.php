<?php

namespace Controller\Admin\Goods;

use App;

class SubscriptionHolidayController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->callMenu("goods", "subscription", "holiday");

        $obj = new \Component\Subscription\Subscription();
        if ($obj->applyFl) {
            $list = $obj->getHolidayList();
            $this->setData("list", $list);
            
            $this->setData('wmSubscription', true);
        }
    }
}