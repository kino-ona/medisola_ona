<?php

namespace Controller\Mobile\Mypage;

class WishListController extends \Bundle\Controller\Mobile\Mypage\WishListController
{
    public $db = null;

    public function index()
    {
        parent::index();

        $this->db = \App::load('DB');
        $wishInfo = $this->getData('wishInfo');
        foreach ($wishInfo as $key => $val) {
            foreach ($val as $key2 => $val2) {
                foreach ($val2 as $key3 => $val3) {
                    $goodsInfo = $this->db->fetch("select * from wm_firstDelivery where goodsNo='{$val3['goodsNo']}'");
                    if (!empty($goodsInfo)) {
                        $wishInfo[$key][$key2][$key3]['orderPossible'] = 'n'; // 첫배송 상품 주문 불가 처리
                    }
                }
            }
        }

        $this->setData('wishInfo', $wishInfo);
    }
}