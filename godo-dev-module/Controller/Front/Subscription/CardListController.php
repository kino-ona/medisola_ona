<?php

namespace Controller\Front\Subscription;

use App;
use Session;
use Request; 

class CardListController extends \Controller\Front\Controller 
{
    public function index()
    {
        if (!gd_is_login()) 
            return $this->js("alert('로그인이 필요한 페이지 입니다.');window.location.href='../member/login.php?returnUrl=../subscription/card_list.php';");
        
        $obj = App::load(\Component\Subscription\Subscription::class);   
        $cfg = $obj->getCfg();
        $this->getView()->setDefine("pg_gate", $cfg['pg_gate']);
        $this->setData("subCfg", $cfg);
        
        $member = Session::get("member");
        $this->setData("member", $member);
        
        $list = $obj->getCards($member['memNo']);
        $this->setData("list", $list);
        
        $returnUrl = Request::get()->get("returnUrl");
        Session::set("cardReturnUrl", $returnUrl);
        $returnUrl = Session::get("cardReturnUrl");
    }
}