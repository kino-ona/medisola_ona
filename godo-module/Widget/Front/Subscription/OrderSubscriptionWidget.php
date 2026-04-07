<?php
namespace Widget\Front\Subscription;

use App;
use Request;

class OrderSubscriptionWidget extends \Widget\Front\Widget 
{
    public function index()
    {
        $obj = App::load(\Component\Subscription\Subscription::class);
        $cfg = $obj->getCfg();
        $this->setData("subCfg", $cfg);

        $scheduleList = $this->getData("scheduleList");
        $this->setData("scheduleList", $scheduleList);
        
        $period = $this->getData("period");
        $deliveryEa = $this->getData("deliveryEa");
        
        $this->setData("period", $period);
        $this->setData("deliveryEa", $deliveryEa);
    }
}