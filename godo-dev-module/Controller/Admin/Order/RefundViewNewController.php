<?php

namespace Controller\Admin\Order;

use Framework\Debug\Exception\AlertCloseException;
use App;
use Request;
use Exception;
use Globals;
use Session;

class RefundViewNewController extends \Bundle\Controller\Admin\Order\RefundViewNewController
{
    public function index()
    {
        parent::index();

        // 웹앤모바일 정기결제 기능 추가 ================================================== START
        $obj = new \Component\Subscription\Subscription();
        if ($obj->applyFl) {
            if ($obj->chkSubscriptionOrder(Request::get()->get('orderNo'))) {
                $this->setData('wmSubscription', true);
            }
        }
        // 웹앤모바일 정기결제 기능 추가 ================================================== END
    }
}
