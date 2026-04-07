<?php
namespace Widget\Front\Subscription;

use App;
use Session;

class SelectCardWidget extends \Widget\Front\Widget
{
    public function index()
    {
         if (!gd_is_login()) 
             return;
         
         $obj = App::load(\Component\Subscription\Subscription::class);
         $cfg = $obj->getCfg();
         
        $member = Session::get("member");
        $this->setData("member", $member);
        
        $list = $obj->getCards($member['memNo']);
        $this->setData("list", $list);
         
    }
}