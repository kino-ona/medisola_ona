<?php
namespace Controller\Mobile\Subscription;

use App;
use Request;
use Component\Mall\Mall;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;

class CartController extends \Controller\Mobile\Controller 
{
    public function index()
    {   
        if (!gd_is_login())
            return $this->js("alert('로그인이 필요한 페이지 입니다.');window.location.href='../member/login.php';");
        
        try {
            $cart = App::load(\Component\Subscription\Cart::class);
            
			//$cartInfo = $cart->getCartList();

            $newCartInfo = $period = [];
            if ($cartInfo = $cart->getCartList()) {
                foreach ($cartInfo as $keys => $values) {
                    foreach ($values as $key => $value) {
                        foreach ($value as $k => $v) {
                            $v['period'] = $v['period']?$v['period']:"1_week";
                            $newCartInfo[$v['period']][$keys][$key][] = $v;
                            $period[] = $v['period'];
                        }  // endforeach 
                    } // endforeach 
                } // endforeach 
            }

            $period = array_unique($period);
            $this->setData("periodList", $period);
            $this->setData("newCartInfo", $newCartInfo);
            $this->setData('cartInfo', $cartInfo);
            
            $currency = gd_isset(Mall::getSession('currencyConfig')['code'], 'KRW');
            $goodsNo = [];
            foreach ($cartInfo as $key => $val) {// 상품번호 추출
                foreach ($val as $key2 => $val2) {
                    foreach ($val2 as $key3 => $val3) {
                         $goodsNo[] = $val3['goodsNo'];
                    }
                }
            }
           
            // 쿠폰 설정값 정보
           $couponConfig = gd_policy('coupon.config');
           $this->setData('couponUse', gd_isset($couponConfig['couponUseType'], 'n')); // 쿠폰 사용여부
           $this->setData('couponConfig', gd_isset($couponConfig)); // 쿠폰설정
           
           // 마일리지 지급 정보
           $this->setData('mileage', $cart->mileageGiveInfo['info']);
           
            // 장바구니 객체에서 계산된 상품정보
            $this->setData('cartCnt', $cart->cartCnt); // 장바구니 수량
            $this->setData('shoppingUrl', $cart->shoppingUrl); // 쇼핑 계속하기 URL
            $this->setData('cartScmInfo', $cart->cartScmInfo); // 장바구니 SCM 정보
            $this->setData('cartScmCnt', $cart->cartScmCnt); // 장바구니 SCM 수량
            $this->setData('cartScmGoodsCnt', $cart->cartScmGoodsCnt); // 장바구니 SCM 상품 갯수
            $this->setData('totalGoodsPrice', $cart->totalGoodsPrice); // 상품 총 가격
            $this->setData('totalGoodsDcPrice', $cart->totalGoodsDcPrice); // 상품 할인 총 가격
            $this->setData('totalGoodsMileage', $cart->totalGoodsMileage); // 상품별 총 상품 마일리지
            $this->setData('totalScmGoodsPrice', $cart->totalScmGoodsPrice); // SCM 별 상품 총 가격
            $this->setData('totalScmGoodsDcPrice', $cart->totalScmGoodsDcPrice); // SCM 별 상품 할인 총 가격
            $this->setData('totalScmGoodsMileage', $cart->totalScmGoodsMileage); // SCM 별 총 상품 마일리지
            $this->setData('totalMemberDcPrice', $cart->totalMemberDcPrice); // 회원 그룹 추가 할인 총 가격
            $this->setData('totalMemberBankDcPrice', $cart->totalMemberBankDcPrice); // 회원 등급할인 브랜드 무통장 할인 총 가격
            $this->setData('totalMemberOverlapDcPrice', $cart->totalMemberOverlapDcPrice); // 회원 그룹 중복 할인 총 가격
            $this->setData('totalScmMemberDcPrice', $cart->totalScmMemberDcPrice); // scm 별 회원 그룹 추가 할인 총 가격
            $this->setData('totalScmMemberOverlapDcPrice', $cart->totalScmMemberOverlapDcPrice); // scm 별 회원 그룹 중복 할인 총 가격
            $this->setData('totalSumMemberDcPrice', $cart->totalSumMemberDcPrice); // 회원 할인 총 금액
            $this->setData('totalMemberMileage', $cart->totalMemberMileage); // 회원 그룹 총 마일리지
            $this->setData('totalScmMemberMileage', $cart->totalScmMemberMileage); // scm 별 회원 그룹 총 마일리지
            $this->setData('totalCouponGoodsDcPrice', $cart->totalCouponGoodsDcPrice); // 상품 총 쿠폰 금액
            $this->setData('totalScmCouponGoodsDcPrice', $cart->totalScmCouponGoodsDcPrice); // scm 별 상품 총 쿠폰 금액
            $this->setData('totalCouponGoodsMileage', $cart->totalCouponGoodsMileage); // 상품 총 쿠폰 마일리지
            $this->setData('totalScmCouponGoodsMileage', $cart->totalScmCouponGoodsMileage); // scm 별 상품 총 쿠폰 마일리지
            $this->setData('totalDeliveryCharge', $cart->totalDeliveryCharge); // 상품 배송정책별 총 배송 금액
            $this->setData('totalScmGoodsDeliveryCharge', $cart->totalScmGoodsDeliveryCharge); // SCM 별 총 배송 금액
            $this->setData('totalSettlePrice', $cart->totalSettlePrice); // 총 결제 금액 (예정)
            $this->setData('totalMileage', $cart->totalMileage); // 총 적립 마일리지 (예정)
            $this->setData('orderPossible', $cart->orderPossible); // 주문 가능 여부
            $this->setData('orderPossibleMessage', $cart->orderPossibleMessage); // 주문 불가 사유
            $this->setData('setDeliveryInfo', $cart->setDeliveryInfo); // 배송비조건별 배송 정보   
           
           $obj = App::load(\Component\Subscription\Subscription::class);
           $cfg = $obj->getCfg();
           $this->setData("subCfg", $cfg);

           $scheduleList = $obj->setCartInfo($cartInfo)
               ->setTotalDeliveryCharge($cart->totalDeliveryCharge)
                                     ->setPrice($cart->totalGoodsPrice)
                                     ->setMode("getScheduleList")
                                     ->get();
           $this->setData("scheduleList", $scheduleList);
          
        } catch (AlertRedirectException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new AlertOnlyException(__('오류') . ' - ' . __('오류가 발생 하였습니다.'), null, null, '../subscription/cart.php', 'parent');
        }
        
        //$this->setData('gPageName', "장바구니");
    }
}