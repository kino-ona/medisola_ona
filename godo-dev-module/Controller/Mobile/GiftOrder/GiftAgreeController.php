<?php

namespace Controller\Mobile\GiftOrder;

class GiftAgreeController extends \Controller\Mobile\Controller
{
    public function index()
    {
        $orderNo = \Request::get()->get("orderNo");
        $this->setData('orderNo', $orderNo);
    }
}