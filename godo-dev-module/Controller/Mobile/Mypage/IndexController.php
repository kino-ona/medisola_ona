<?php

namespace Controller\Mobile\Mypage;

class IndexController extends \Bundle\Controller\Mobile\Mypage\IndexController
{
    public function index()
    {
        parent::index();

        // 웹앤모바일 정기결제 기능 추가 ================================================== START
        $obj = new \Component\Subscription\Subscription();
        if ($obj->applyFl) {
            $this->setData('wmSubscription', true);
        }
        // 웹앤모바일 정기결제 기능 추가 ================================================== END
    }
}