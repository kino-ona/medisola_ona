<?php
namespace Widget\Front\Subscription;

use App;
use Session;

class CartTabWidget extends \Widget\Front\Widget 
{
    public function index()
    {
        $tab = $this->getData('tab');
        $this->setData("tab", $tab);
        
        $db = App::load('DB');
        
        
        if (gd_is_login()) {
            $memNo = Session::get("member.memNo");
            $row = $db->fetch("SELECT COUNT(*) as cnt FROM " . DB_CART . " WHERE memNo='{$memNo}'");
            $this->setData("cartCnt", $row['cnt']);
            
            $row = $db->fetch("SELECT COUNT(*) as cnt FROM  wm_subscription_cart WHERE memNo='{$memNo}' AND isTemp='0'");
            $this->setData("subCartCnt", $row['cnt']);
        } else {
            $cartCnt = $this->getData("cartCnt");
            $this->setData("cartCnt", $cartCnt);
        }
       

    }  
}