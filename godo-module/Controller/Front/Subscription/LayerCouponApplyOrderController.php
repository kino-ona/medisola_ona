<?php
namespace Controller\Front\Subscription;

use App;
use Session;
use Request;
use Exception;
use Framework\Utility\ArrayUtils;

class LayerCouponApplyOrderController extends \Controller\Front\Controller 
{
    public function index()
    {
        try {
            if (!Request::isAjax()) {
                throw new Exception('Ajax ' . __('전용 페이지 입니다.'));
            }

            // 로그인 체크
            if(Session::has('member')) {
                $post = Request::post()->toArray();
                $cart = App::load(\Component\Subscription\Cart::class);
                $coupon = App::load(\Component\Coupon\Coupon::class);

                // 장바구니(주문)의 상품 데이터
                $cartInfo = $cart->getCartList($post['cartSno']);
                $this->setData('cartInfo', $cartInfo);
                $couponConfig = gd_policy('coupon.config');
                $this->setData('productCouponChangeLimitType', $couponConfig['productCouponChangeLimitType']); //상품쿠폰이 주문서 수정 제한 여부

                $goodsPriceArr = [
                    'goodsPriceSum'=>$cart->totalPrice['goodsPrice'],
                    'optionPriceSum'=>$cart->totalPrice['optionPrice'],
                    'optionTextPriceSum'=>$cart->totalPrice['optionTextPrice'],
                    'addGoodsPriceSum'=>$cart->totalPrice['addGoodsPrice'],
                ];
                if($post['couponApplyOrderNo']) {
                    // 장바구니에 사용된 회원쿠폰 리스트
                    $cartCouponNoArr = explode(INT_DIVISION,$post['couponApplyOrderNo']);
                    foreach($cartCouponNoArr as $cartCouponKey => $cartCouponVal) {
                        $cartCouponArrData[$cartCouponKey] = $coupon->getMemberCouponInfo($cartCouponVal);
                    }
                    // 장바구니에 사용된 회원쿠폰 리스트를 보기용으로 변환
                    $convertCartCouponArrData = $coupon->convertCouponArrData($cartCouponArrData);
                    // 장바구니에 사용된 회원쿠폰의 정율도 정액으로 계산된 금액
                    $convertCartCouponPriceArrData = $coupon->getMemberCouponPrice($goodsPriceArr, $post['couponApplyOrderNo']);
                    $this->setData('cartCouponArrData', $cartCouponArrData);
                    $this->setData('convertCartCouponArrData', $convertCartCouponArrData);
                    $this->setData('convertCartCouponPriceArrData', $convertCartCouponPriceArrData);
                }

                // 해당 상품의 사용가능한 회원쿠폰 리스트
                $memberCouponArrData = $coupon->getOrderMemberCouponList(Session::get('member.memNo'), $cart->payLimit);
                if(is_array($memberCouponArrData['order'])){
                    $memberCouponNoArr['order'] = array_column($memberCouponArrData['order'],'memberCouponNo');
                    if ($memberCouponNoArr['order']) {
                        $memberCouponNoString['order'] = implode(INT_DIVISION, $memberCouponNoArr['order']);
                        // 해당 상품의 사용가능한 회원쿠폰 리스트를 보기용으로 변환
                        $convertMemberCouponArrData['order'] = $coupon->convertCouponArrData($memberCouponArrData['order']);
                        // 해당 상품의 사용가능한 회원쿠폰의 정율도 정액으로 계산된 금액
                        $convertMemberCouponPriceArrData['order'] = $coupon->getMemberCouponPrice($goodsPriceArr, $memberCouponNoString['order']);
                    }
                }
                if(is_array($memberCouponArrData['delivery'])){
                    $memberCouponNoArr['delivery'] = array_column($memberCouponArrData['delivery'],'memberCouponNo');
                    if ($memberCouponNoArr['delivery']) {
                        $memberCouponNoString['delivery'] = implode(INT_DIVISION, $memberCouponNoArr['delivery']);
                        // 해당 상품의 사용가능한 회원쿠폰 리스트를 보기용으로 변환
                        $convertMemberCouponArrData['delivery'] = $coupon->convertCouponArrData($memberCouponArrData['delivery']);
                        // 해당 상품의 사용가능한 회원쿠폰의 정율도 정액으로 계산된 금액
                        $convertMemberCouponPriceArrData['delivery'] = $coupon->getMemberCouponPrice($goodsPriceArr, $memberCouponNoString['delivery']);
                    }
                }

                $this->setData('memberCouponArrData', $memberCouponArrData);
                $this->setData('convertMemberCouponArrData', $convertMemberCouponArrData);
                $this->setData('convertMemberCouponPriceArrData', $convertMemberCouponPriceArrData);

                // 해당 상품의 사용가능한 상품쿠폰 리스트
                if($couponConfig['productCouponChangeLimitType'] == 'n') { // 상품쿠폰 주문서페이지 변경 제한안함일 때
                    $goodsCouponData = $coupon->getProductCouponChangeData('layer', $cartInfo, $goodsPriceArr);
                    $this->setData('cartCouponNoArr', $goodsCouponData['cartCouponNoArr']); // cart 쿠폰 데이터
                    $goodsCouponSnoString = [];
                    foreach($goodsCouponData['cartCouponNoArr'] as $cNoKey => $cNoVal) { // 적용된 상품 cart 쿠폰배열
                        foreach($cNoVal as $cNoSnoVal) {
                            if($cNoSnoVal) {
                                $goodsCouponSnoString[] = $cNoKey . INT_DIVISION . $cNoSnoVal;
                            }
                        }
                    }
                    $this->setData('goodsCouponSnoArr', $goodsCouponData['goodsCouponSnoArr']);  // 카트 일련번호 매칭
                    $this->setData('cartCouponNoDivisionArr', implode(STR_DIVISION, $goodsCouponSnoString)); // cart 쿠폰 데이터 매칭 배열 string 변환
                    $this->setData('goodsCouponArrData', $goodsCouponData['goodsCouponArrData']); // 쿠폰 DB 데이터
                    $this->setData('convertGoodsCouponArrData', $goodsCouponData['convertGoodsCouponArrData']); // 변환 쿠폰 데이터
                    $this->setData('convertGoodsCouponPriceArrData', $goodsCouponData['convertGoodsCouponPriceArrData']); // 쿠폰 가격 데이터
                    unset($goodsCouponData);
                    unset($goodsCouponSnoString);
                }
                $this->setData('couponApplyOrderNo', $post['couponApplyOrderNo']);
            } else {
                $this->json([
                    'error' => 10,
                    'message' => __('로그인하셔야 해당 서비스를 이용하실 수 있습니다.'),
                ]);
            }
        } catch (Exception $e) {
            $this->json([
                'error' => 0,
                'message' => $e->getMessage(),
            ]);
        }
    }
}