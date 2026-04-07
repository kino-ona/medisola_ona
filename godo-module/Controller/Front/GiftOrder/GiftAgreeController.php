<?php

namespace Controller\Front\GiftOrder;

class GiftAgreeController extends \Controller\Front\Controller
{
    public function index()
    {
        $orderNo = \Request::get()->get("orderNo");
        $this->setData('orderNo', $orderNo);
    }
}