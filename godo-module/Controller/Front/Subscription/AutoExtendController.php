<?php
namespace Controller\Front\Subscription;

use App;
use Request;
use Session;

class AutoExtendController extends \Controller\Front\Controller 
{
    public function index()
    {
        $this->getView()->setDefine("layout", "layout_blank");
        if (!$uid = Request::get()->get("uid"))
            return $this->js("alert('잘못된 접근입니다.');");
        
        if (!gd_is_login())
            return $this->js("alert('로그인이 필요합니다.');");
         
       $memNo = Session::get("member.memNo");
       
       
       $obj = App::load(\Component\Subscription\Subscription::class);
       if (!$info = $obj->setUid($uid)->setMode("subscriptionInfo")->get())
           return $this->js("alert('신청 정보가 존재하지 않습니다.');");
       
       
       if ($info['memNo'] != $memNo) 
           return $this->js("alert('본인이 신청하신 정보만 수정하실 있습니다.');");
       
       $this->setData($info);
       $this->setData("uid", $uid);
    }
}