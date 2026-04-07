<?php

namespace Controller\Mobile\Order;

use Request;
use App;
use Component\Wm\UseGift;
use Session;
use Component\Wm\FirstDelivery;

class CartController extends \Bundle\Controller\Mobile\Order\CartController
{
    public function index()
    {
        parent::index();

        $getUseGift = App::load(UseGift::class);

        $setFl = 0;

        $isUse = $getUseGift->GiftUseSetCart();

        $cartInfo = $this->getData('cartInfo');

        foreach ($cartInfo as $key => $value) {
            foreach ($value as $keys => $values) {
                foreach ($values as $keyss => $valuess) {
                    if ($valuess['firstDelivery'] > 0) {
                        $firstTime = strtotime($valuess['firstDelivery']);

                        $md = date('m-d', $firstTime);
                        $md = str_replace('-', '월 ', $md);
                        $md .= '일';
                        /*
                        $daily = array('일','월','화','수','목','금','토');

                        $w = $daily[date('w' , strtotime($valuess['firstDelivery']))];
                        $cartInfo[$key][$keys][$keyss]['firstDelivery'] = $w.'요일';
                        */
                        $cartInfo[$key][$keys][$keyss]['firstDelivery'] = $md;
                    }


                    $goodsGiftUse = $getUseGift->getUseGift($valuess['goodsNo']);
                    //$isUse = $getUseGift ->GiftUseSetCart($valuess['goodsNo']);
                    $cartInfo[$key][$keys][$keyss]['useGift'] = $goodsGiftUse['useGift'];
                    $cartInfo[$key][$keys][$keyss]['isUse'] = $isUse['isUse'];
                    $cartInfo[$key][$keys][$keyss]['useRange'] = $isUse['useRange'];

                    //상품 개별설정 사용여부
                    if (($goodsGiftUse['useGift'] == 1 && $isUse['useRange'] == 'goods' && $isUse['isUse'] == 1) || ($isUse['isUse'] == 1 && $isUse['useRange'] == 'all')) {
                        $setFl = 1;
                    }
                }
            }
        }

        // 선물하기 사용설정
        if ($isUse['isUse'] == 1) {
            $isUseFl = 1;
            $this->setData('isUse', $isUseFl);
        }

        $cartCnt = $this->getData('cartCnt');
        $this->setData('setFl', $setFl);
        $this->setData('cartInfo', $cartInfo);

        // 웹앤모바일 정기결제 기능 추가 ================================================== START
        $obj = new \Component\Subscription\Subscription();
        if ($obj->applyFl) {
            $this->setData('wmSubscription', true);
        }
        // 웹앤모바일 정기결제 기능 추가 ================================================== END
    }
}