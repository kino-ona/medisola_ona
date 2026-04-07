<?php

namespace Controller\Admin\Order;

use App;
use Request;

class SubscriptionOrderListAllController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->callMenu("order", "subscription", "order_list");

        $objAdmin = new \Component\Subscription\SubscriptionAdmin();
        if ($objAdmin->applyFl) {
            $status = $objAdmin->getOrderStatusList();
            $result = $objAdmin->orderList();
            $this->setData("status", $status);
            $this->setData($result);

            $get = Request::get()->toArray();
            $this->setData("search", $get);

            $this->setData('wmSubscription', true);
        }
    }
}