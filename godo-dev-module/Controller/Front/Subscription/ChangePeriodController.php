<?php

namespace Controller\Front\Subscription;

use App;
use Request;

class ChangePeriodController extends \Controller\Front\Controller 
{
    public function index()
    {
        if (!$idx = Request::get()->get("idx"))
            return $this->js("alert('잘못된 접근입니다.');");
        
        $cart = App::load(\Component\Subscription\Cart::class);
        $info = $cart->getCartInfo($idx);
        $this->setData("info", $info);
        
        $obj = App::load(\Component\Subscription\Subscription::class);
        $cfg = $obj->getCfg();
        $this->setData("subCfg", $cfg);
        
    }
}