<?php

namespace Controller\Mobile\Mypage;

use Component\Database\DBTableField;
use Component\Goods\GoodsCate;
use Component\Page\Page;
use Cookie;
use Exception;
use Framework\Debug\Exception\AlertBackException;
use Request;
use Session;

class LayerOrderRefundRegistController extends \Bundle\Controller\Mobile\Mypage\LayerOrderRefundRegistController
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
