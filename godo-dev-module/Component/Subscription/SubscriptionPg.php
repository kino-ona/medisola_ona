<?php
namespace Component\Subscription;

use Component\Subscription\SubscriptionTrait;
use App;

abstract class SubscriptionPg
{
    use SubscriptionTrait;
    
    public function __construct()
    {
        $this->setCfg();
    }
    
    /* PG설정 */
    abstract protected function getPgCfg(); 
    
    /* 정기결제 결제 처리 */
    abstract public function pay($idx, $chkStamp, $isManual);
    
    /* 정기결제 취소 처리 */
    abstract public function cancel($orderNo, $isApplyRefund, $msg);
}