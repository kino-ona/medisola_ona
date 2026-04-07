<?php

namespace Controller\Admin\Goods;

use App;

class SubscriptionConfigController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->callMenu("goods", "subscription", "config");

        $obj = new \Component\Subscription\Subscription();
        if ($obj->applyFl) {
            $cfg = $obj->getCfg();
            $this->setData($cfg);
            
            $this->setData('wmSubscription', true);
        }
    }
}