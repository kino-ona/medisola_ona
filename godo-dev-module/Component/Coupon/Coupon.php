<?php

namespace Component\Coupon;

use App;
use Component\Member\Group\Util as GroupUtil;
use Component\Member\Util\MemberUtil;
use Component\Deposit\Deposit;
use Component\Mileage\Mileage;
use Component\Sms\Code;
use Component\Sms\SmsAutoCode;
use Component\Storage\Storage;
use Component\Cart\CartAdmin;
use Framework\Object\SimpleStorage;
use Framework\Utility\ArrayUtils;
use Globals;
use Request;
use Session;

class Coupon extends \Bundle\Component\Coupon\Coupon
{
	/**
     * 상품쿠폰이 주문서 수정이 허용일 때 제한 사항 검증 - product 주문허용
     *
     * @param array    $memberCouponNo 쿠폰번호
     * @return int      $goodsPrice 가격
     *
     * @author tomi
     */
    public function getProductCouponGiftUsableCheck($memberCouponNo, $dataCartSno, $cartInfo,  $cartGoodsPrice)
    {
        if($dataCartSno) {
            $memberCouponArrNo = explode(INT_DIVISION, $memberCouponNo);
            $memberCouponUsable = true;
            // 카트 데이터 호출 후 상품정보 가져오기(Sno에 해당되는 상품 것만)
            $cart = \App::load('\\Component\\GiftOrder\\CartGift');
            $cartData = $cart->getCartGoodsData($dataCartSno);
            $scmCartInfo = array_shift($cartData);
            $goodsCartInfo = array_shift($scmCartInfo)[0];

            foreach ($memberCouponArrNo as $val) {
                $memberCouponState = $this->getMemberCouponInfo($val, 'mc.memberCouponState,mc.memberCouponStartDate,mc.memberCouponEndDate,c.couponMinOrderPrice,c.couponProductMinOrderType');

                if ($memberCouponState['memberCouponState'] == 'y' || $memberCouponState['memberCouponState'] == 'cart') {
                    // 쿠폰 사용 시작일
                    if (strtotime($memberCouponState['memberCouponStartDate']) > 0) {
                        if (strtotime($memberCouponState['memberCouponStartDate']) > time()) { // 쿠폰 사용 시작일이 지금 시간 보다 크다면 제한
                            $usable = 'EXPIRATION_START_PERIOD';
                        } else {
                            $usable = 'YES';
                        }
                        // 쿠폰 사용 만료일
                    } else if (strtotime($memberCouponState['memberCouponEndDate']) > 0) {
                        if (strtotime($memberCouponState['memberCouponEndDate']) < time()) { // 쿠폰 사용 만료일이 지금 시간 보다 작다면 제한
                            $usable = 'EXPIRATION_END_PERIOD';
                        } else {
                            $usable = 'YES';
                        }
                    } else {
                        $usable = 'YES';
                    }
                    // 상품쿠폰 주문에서 적용 시
                    $thisCallController = \App::getInstance('ControllerNameResolver')->getControllerName();
                    if (($this->couponConfig['productCouponChangeLimitType'] == 'n') && ($thisCallController == 'Controller\Front\GiftOrder\CartPsController' || $thisCallController == 'Controller\Mobile\GiftOrder\CartPsController')) {
                        // 최소 상품구매금액 제한
                        if($memberCouponState['couponProductMinOrderType'] == 'order') {
                            // Sno상관없이 주문 시 생성된 cart 상품가격 정보 가져오기
                            // 카트 데이터 호출 후 상품정보 가져오기(Sno에 해당되는 상품 것만)
                            $goodsCouponForTotalPrice = $cart->getProductCouponGoodsAllPrice($cartInfo, $memberCouponNo, 'front', '2');
                            $goodsCartInfo['price'] = $goodsCouponForTotalPrice;
                        }
                        // 할인/적립 기준금액
                        $totalGoodsPrice = $goodsCartInfo['price']['goodsPriceSum'];
                        if ($this->couponConfig['couponOptPriceType'] == 'y') {
                            $totalGoodsPrice += $goodsCartInfo['price']['optionPriceSum'];
                        }
                        if ($this->couponConfig['couponTextPriceType'] == 'y') {
                            $totalGoodsPrice += $goodsCartInfo['price']['optionTextPriceSum'];
                        }
                        if ($this->couponConfig['couponAddPriceType'] == 'y') {
                            $totalGoodsPrice += $goodsCartInfo['price']['addGoodsPriceSum'];
                        }
                        // 금액 체크
                        if ($memberCouponState['couponMinOrderPrice'] > $totalGoodsPrice) {
                            $usable = 'NO';
                        } else {
                            $usable = 'YES';
                        }
                        /* 타임 세일 관련 */
                        if (gd_is_plus_shop(PLUSSHOP_CODE_TIMESALE) === true) {
                            if($goodsCartInfo[0]['timeSaleFl'] == true) {
                                $strScmSQL = 'SELECT ts.couponFl as timeSaleCouponFl,ts.sno as timeSaleSno,ts.goodsNo as goodsNo FROM ' . DB_TIME_SALE . ' as ts WHERE FIND_IN_SET(' . $goodsCartInfo[0]['goodsNo'] . ', REPLACE(ts.goodsNo,"' . INT_DIVISION . '",",")) AND UNIX_TIMESTAMP(ts.startDt) < UNIX_TIMESTAMP() AND  UNIX_TIMESTAMP(ts.endDt) > UNIX_TIMESTAMP() AND ts.pcDisplayFl="y"';
                                $tmpScmData = $this->db->query_fetch($strScmSQL, null, false);
                                if ($tmpScmData) {
                                    if ($tmpScmData['timeSaleCouponFl'] == 'n') {
                                        $usable = 'NO';
                                    }
                                }
                                unset($tmpScmData);
                                unset($strScmSQL);
                            }
                        }
                    }
                } else {
                    $usable = 'NO';
                }
                if ($usable == 'YES' && $memberCouponUsable) {
                    $memberCouponUsable = true;
                } else {
                    $memberCouponUsable = false;
                }
            }
            return $memberCouponUsable;
        }
    }
	

}