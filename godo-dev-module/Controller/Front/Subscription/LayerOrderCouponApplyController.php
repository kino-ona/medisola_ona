<?php
namespace Controller\Front\Subscription;

use App;
use Session;
use Request;
use Exception;
use Framework\Utility\ArrayUtils;

class LayerOrderCouponApplyController extends \Controller\Front\Controller 
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

                // 장바구니의 해당 장바구니고유번호의 데이터
                $cartInfo = $cart->getCartList($post['cartSno']);
                
                $scmCartInfo = array_shift($cartInfo);
                $goodsCartInfo =  array_shift($scmCartInfo);
                $goodsPriceArr = [
                    'goodsCnt'=>$goodsCartInfo[0]['goodsCnt'],
                    'goodsPriceSum'=>$goodsCartInfo[0]['price']['goodsPriceSum'],
                    'optionPriceSum'=>$goodsCartInfo[0]['price']['optionPriceSum'],
                    'optionTextPriceSum'=>$goodsCartInfo[0]['price']['optionTextPriceSum'],
                    'addGoodsPriceSum'=>$goodsCartInfo[0]['price']['addGoodsPriceSum'],
                ];
                if($goodsCartInfo[0]['memberCouponNo']) {
                    // 장바구니에 사용된 회원쿠폰 리스트
                    $cartCouponNoArr = explode(INT_DIVISION,$goodsCartInfo[0]['memberCouponNo']);
                    foreach($cartCouponNoArr as $cartCouponKey => $cartCouponVal) {
                        $cartCouponArrData[$cartCouponKey] = $coupon->getMemberCouponInfo($cartCouponVal);
                        $nowMemberCouponNoArr[] = $cartCouponArrData[$cartCouponKey]['couponNo'];
                    }
                    // 장바구니에 사용된 회원쿠폰 리스트를 보기용으로 변환
                    $convertCartCouponArrData = $coupon->convertCouponArrData($cartCouponArrData);
                    // 장바구니에 사용된 회원쿠폰의 정율도 정액으로 계산된 금액
                    $convertCartCouponPriceArrData = $coupon->getMemberCouponPrice($goodsPriceArr, $goodsCartInfo[0]['memberCouponNo']);
                    $this->setData('cartCouponArrData', $cartCouponArrData);
                    $this->setData('convertCartCouponArrData', $convertCartCouponArrData);
                    $this->setData('convertCartCouponPriceArrData', $convertCartCouponPriceArrData);
                }
               
                // 해당 상품의 사용가능한 회원쿠폰 리스트
                $memberCouponArrData = $coupon->getGoodsMemberCouponList($goodsCartInfo[0]['goodsNo'],Session::get('member.memNo'),Session::get('member.groupSno'),null,$nowMemberCouponNoArr, 'cart');
                if(is_array($memberCouponArrData)){
                    $memberCouponNoArr = array_column($memberCouponArrData,'memberCouponNo');
                    if ($memberCouponNoArr) {
                        $memberCouponNoString = implode(INT_DIVISION, $memberCouponNoArr);
                        // 해당 상품의 사용가능한 회원쿠폰 리스트를 보기용으로 변환
                        $convertMemberCouponArrData = $coupon->convertCouponArrData($memberCouponArrData);
                        // 해당 상품의 사용가능한 회원쿠폰의 정율도 정액으로 계산된 금액
                        $convertMemberCouponPriceArrData = $coupon->getMemberCouponPrice($goodsPriceArr, $memberCouponNoString);
                    }
                }
               
                /* 정기결제 장바구니에 속해있는 쿠폰 제외 START */
                $db = App::load('DB');
                
                $couponList = [];
                $memNo = Session::get("member.memNo");
                $sql = "SELECT memberCouponNo FROM wm_subscription_cart WHERE memNo='{$memNo}' AND memberCouponNo <> ''";
                if ($list = $db->query_fetch($sql)) {
                    foreach ($list as $li) {
                       $couponNo = explode(INT_DIVISION, $li['memberCouponNo']);
                       $couponList = array_merge($couponList, $couponNo);
                    }
                }
                
               
                $couponList = array_unique($couponList);
                foreach ($memberCouponArrData as $k => $v) {
                    if (in_array($v['memberCouponNo'], $couponList)) {
                        unset($memberCouponArrData[$k]);
                    } else {    
                        $memberCouponArrData[$k] = $v;
                    }
                }
                
                $memberCouponArrData = array_values($memberCouponArrData);
                /* 정기결제 장바구니에 속해있는 쿠폰 제외 END */
                
                $this->setData('memberCouponArrData', $memberCouponArrData);
                $this->setData('convertMemberCouponArrData', $convertMemberCouponArrData);
                $this->setData('convertMemberCouponPriceArrData', $convertMemberCouponPriceArrData);
                $this->setData('goodsNo', $goodsCartInfo[0]['goodsNo']);
                $this->setData('memberCouponNo', $goodsCartInfo[0]['memberCouponNo']);
                $this->setData('cartSno', $post['cartSno']);
                $this->setData('action', $post['action']);
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