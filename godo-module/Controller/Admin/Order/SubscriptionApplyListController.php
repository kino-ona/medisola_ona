<?php

namespace Controller\Admin\Order;

use App;
use Request;

class SubscriptionApplyListController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->callMenu("order", "subscription", "apply_list");

        $objAdmin = new \Component\Subscription\SubscriptionAdmin();
        if ($objAdmin->applyFl) {
            $status = $objAdmin->getOrderStatusList();
            $this->setData("status", $status);
            $result = $objAdmin->getApplyList(10);
            $this->setData($result);

            $get = Request::get()->toArray();
            $this->setData("search", $get);

            $this->setData('wmSubscription', true);
        }
    }
}